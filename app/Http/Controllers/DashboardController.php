<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

/** FR-26 to FR-29 for managers and administrators; a simple home page for everyone else. */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isManager()) {
            return view('dashboard', $this->reportData($user));
        }

        $assigned = $user->isSpeaker() || $user->isGateStaff()
            ? Event::with('chapter')
                ->whereHas('assignments', fn ($q) => $q->where('user_id', $user->id))
                ->orderBy('starts_at')->get()
            : collect();

        return view('dashboard', [
            'reports' => false,
            'assigned' => $assigned,
            'registrations' => $user->isAttendee()
                ? $user->registrations()->with('event')->latest('registered_at')->take(5)->get()
                : collect(),
        ]);
    }

    private function reportData($user): array
    {
        $events = Event::with('chapter')
            ->when($user->isManager(), fn ($q) => $q->whereHas('chapter', fn ($c) => $c->where('manager_id', $user->id)))
            ->withCount([
                'registrations as taken_count' => fn ($q) => $q->whereIn('payment_status', ['pending', 'paid']),
                'registrations as paid_count' => fn ($q) => $q->where('payment_status', 'paid'),
            ])
            ->orderBy('starts_at')
            ->get()
            ->each(function ($event) {
                // Revenue counts paid registrations only, and is computed rather than stored.
                $event->revenue = $event->paid_count * (float) $event->ticket_price;
            });

        $chapters = $events->groupBy('chapter_id')->map(fn ($group) => [
            'name' => $group->first()->chapter->name,
            'events' => $group->count(),
            'tickets' => $group->sum('taken_count'),
            'paid' => $group->sum('paid_count'),
            'revenue' => $group->sum('revenue'),
        ])->sortByDesc('revenue')->values();

        return [
            'reports' => true,
            'events' => $events,
            'chapters' => $chapters,
            'totals' => [
                'tickets' => $events->sum('taken_count'),
                'revenue' => $events->sum('revenue'),
            ],
        ];
    }
}
