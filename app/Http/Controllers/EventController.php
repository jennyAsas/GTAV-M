<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** FR-06 to FR-11 */
class EventController extends Controller
{
    /** FR-10: browse upcoming events. */
    public function index(Request $request)
    {
        $events = Event::with('chapter')
            ->withCount(['registrations as taken_count' => fn ($q) => $q->whereIn('payment_status', ['pending', 'paid'])])
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->paginate(12);

        $mine = $request->user()->registrations()->get()
            ->mapWithKeys(fn ($r) => [$r->event_id => $r->payment_status->value]);

        return view('events.index', compact('events', 'mine'));
    }

    public function show(Request $request, Event $event)
    {
        $event->load('chapter', 'speakers', 'gateStaff')->loadCount([
            'registrations as taken_count' => fn ($q) => $q->whereIn('payment_status', ['pending', 'paid']),
        ]);

        $user = $request->user();

        return view('events.show', [
            'event' => $event,
            'registration' => $user->registrations()->where('event_id', $event->id)->first(),
            'canManage' => $user->canManageEvent($event),
            'speakerOptions' => $user->canManageEvent($event) ? User::where('role', 'speaker')->orderBy('name')->get() : collect(),
            'gateOptions' => $user->canManageEvent($event) ? User::where('role', 'gate_staff')->orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Event::class);

        return view('events.form', ['event' => new Event(['capacity' => 100, 'ticket_price' => 0]), 'chapters' => $this->chaptersFor($request->user())]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $data = $this->validated($request);
        $this->assertChapterAllowed($request->user(), (int) $data['chapter_id']);

        $event = Event::create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('events.show', $event)->with('status', 'Event created.');
    }

    public function edit(Request $request, Event $event)
    {
        Gate::authorize('manage', $event);

        return view('events.form', ['event' => $event, 'chapters' => $this->chaptersFor($request->user())]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $data = $this->validated($request, $event);
        $this->assertChapterAllowed($request->user(), (int) $data['chapter_id']);

        $event->update($data);

        return redirect()->route('events.show', $event)->with('status', 'Event updated.');
    }

    /** FR-11: assign speakers and gate staff to an event. */
    public function assign(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $data = $request->validate([
            'speakers' => ['array'],
            'speakers.*' => ['integer', Rule::exists('users', 'id')->where('role', 'speaker')],
            'gate_staff' => ['array'],
            'gate_staff.*' => ['integer', Rule::exists('users', 'id')->where('role', 'gate_staff')],
        ]);

        DB::transaction(function () use ($data, $event, $request) {
            foreach (['speaker' => 'speakers', 'gate_staff' => 'gate_staff'] as $assignment => $key) {
                $ids = collect($data[$key] ?? [])->map(fn ($id) => (int) $id)->unique();

                EventAssignment::where('event_id', $event->id)
                    ->where('assignment', $assignment)
                    ->whereNotIn('user_id', $ids)
                    ->delete();

                foreach ($ids as $id) {
                    EventAssignment::firstOrCreate(
                        ['event_id' => $event->id, 'user_id' => $id, 'assignment' => $assignment],
                        ['assigned_by' => $request->user()->id],
                    );
                }
            }
        });

        return back()->with('status', 'Assignments saved.');
    }

    private function validated(Request $request, ?Event $event = null): array
    {
        $minCapacity = $event ? max(1, $event->seatsTaken()) : 1;

        return $request->validate([
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'name' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::in(array_keys(Event::TYPES))],
            'description' => ['nullable', 'string', 'max:5000'],
            'venue' => ['required', 'string', 'max:200'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'registration_cutoff' => ['required', 'date', 'before_or_equal:ends_at'],
            'capacity' => ['required', 'integer', 'min:'.$minCapacity, 'max:100000'],
            'ticket_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ], [
            'capacity.min' => 'Capacity cannot be lower than the seats already taken ('.$minCapacity.').',
        ]);
    }

    private function chaptersFor(User $user)
    {
        return $user->isAdmin() ? Chapter::orderBy('name')->get() : $user->managedChapters()->orderBy('name')->get();
    }

    /** D6: a manager may only create or edit events in their own chapter. */
    private function assertChapterAllowed(User $user, int $chapterId): void
    {
        abort_unless(
            $user->isAdmin() || $user->managedChapters()->whereKey($chapterId)->exists(),
            403,
        );
    }
}
