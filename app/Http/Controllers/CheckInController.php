<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** FR-21 to FR-24 */
class CheckInController extends Controller
{
    public function __construct(private CheckInService $checkIn)
    {
    }

    /** FR-21: pick the event to scan; gate staff see only their assigned, current events (D5). */
    public function index(Request $request)
    {
        Gate::authorize('use-scanner');

        $user = $request->user();

        $events = Event::with('chapter')
            ->where('ends_at', '>=', now()->subDay())
            ->when($user->isGateStaff(), fn ($q) => $q->whereHas('assignments', fn ($a) => $a
                ->where('user_id', $user->id)->where('assignment', 'gate_staff')))
            ->when($user->isManager(), fn ($q) => $q->whereHas('chapter', fn ($c) => $c->where('manager_id', $user->id)))
            ->orderBy('starts_at')
            ->get();

        return view('checkin.index', compact('events'));
    }

    public function show(Event $event)
    {
        Gate::authorize('scan', $event);

        return view('checkin.show', ['event' => $event, 'count' => $this->checkIn->liveCount($event)]);
    }

    public function scan(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('scan', $event);

        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        return response()->json($this->checkIn->scan($request->user(), $event, $data['code']) + [
            'count' => $this->checkIn->liveCount($event),
        ]);
    }

    public function search(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('scan', $event);

        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:80']]);

        return response()->json($this->checkIn->search($event, $data['q'])->map(fn ($r) => [
            'name' => $r->user->name,
            'company' => $r->user->company,
            'code' => $r->ticket_code,
            'paid' => $r->isVerified(),
            'checked_in' => $r->attendance !== null,
        ])->values());
    }

    public function count(Event $event): JsonResponse
    {
        Gate::authorize('scan', $event);

        return response()->json($this->checkIn->liveCount($event));
    }
}
