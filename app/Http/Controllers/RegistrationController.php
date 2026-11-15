<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Services\QrCodeService;
use App\Services\RegistrationException;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** FR-12 to FR-17 */
class RegistrationController extends Controller
{
    public function __construct(private RegistrationService $registrations)
    {
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        try {
            $this->registrations->register($request->user(), $event);
        } catch (RegistrationException $e) {
            return back()->withErrors(['event' => $e->getMessage()]);
        }

        return redirect()->route('events.show', $event)->with('status', 'You are registered.');
    }

    /** FR-15 */
    public function mine(Request $request)
    {
        return view('registrations.mine', [
            'registrations' => $request->user()->registrations()->with('event.chapter')->latest('registered_at')->get(),
        ]);
    }

    /** FR-17: the pass opens only for the owner of a paid registration. */
    public function pass(Registration $registration, QrCodeService $qr)
    {
        Gate::authorize('viewPass', $registration);

        $registration->load('event.chapter', 'user');

        return view('registrations.pass', [
            'registration' => $registration,
            'qrSvg' => $qr->svg($registration->ticket_code),
        ]);
    }

    /** FR-16 and FR-25: registrants of an event with payment and check-in status. */
    public function forEvent(Event $event)
    {
        Gate::authorize('manage', $event);

        return view('events.registrations', [
            'event' => $event->load('chapter'),
            'registrations' => $event->registrations()->with(['user', 'attendance'])->orderBy('registered_at')->paginate(50),
        ]);
    }

    public function markPaid(Request $request, Registration $registration): RedirectResponse
    {
        Gate::authorize('review', $registration);

        $this->registrations->markPaid($registration, $request->user());

        return back()->with('status', 'Payment marked as paid.');
    }

    public function reject(Request $request, Registration $registration): RedirectResponse
    {
        Gate::authorize('review', $registration);

        $this->registrations->reject($registration, $request->user());

        return back()->with('status', 'Registration rejected; the seat is free again.');
    }
}
