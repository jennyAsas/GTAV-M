<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    /**
     * Register an attendee for an event (FR-12, FR-13).
     *
     * The event row is locked inside a transaction so two simultaneous requests for the last
     * seat are serialized. On Postgres this prevents overbooking; SQLite (used by the test
     * suite) ignores row locks, so the concurrency guarantee itself is only verifiable on Postgres.
     *
     * @throws RegistrationException with a message that is safe to show to the user
     */
    public function register(User $user, Event $event): Registration
    {
        if (! $user->isAttendee()) {
            throw new RegistrationException('Only attendee accounts can register for events.');
        }

        try {
            return DB::transaction(function () use ($user, $event) {
                $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

                if (! $locked->isRegistrationOpen()) {
                    throw new RegistrationException('Registration for this event has closed.');
                }

                if (Registration::where('event_id', $locked->id)->where('user_id', $user->id)->exists()) {
                    throw new RegistrationException('You are already registered for this event.');
                }

                if ($locked->seatsRemaining() < 1) {
                    throw new RegistrationException('This event is full.');
                }

                $free = $locked->isFree();

                return Registration::create([
                    'event_id' => $locked->id,
                    'user_id' => $user->id,
                    'ticket_code' => $this->newTicketCode(),
                    'payment_status' => $free ? PaymentStatus::Paid : PaymentStatus::Pending,
                    'paid_at' => $free ? now() : null,
                    'registered_at' => now(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Lost a race on (event_id, user_id).
            throw new RegistrationException('You are already registered for this event.');
        }
    }

    public function markPaid(Registration $registration, User $reviewer): Registration
    {
        $registration->update([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'verified_by' => $reviewer->id,
        ]);

        return $registration;
    }

    /** A rejected registration frees its seat (it no longer counts toward capacity). */
    public function reject(Registration $registration, User $reviewer): Registration
    {
        $registration->update([
            'payment_status' => PaymentStatus::Rejected,
            'paid_at' => null,
            'verified_by' => $reviewer->id,
        ]);

        return $registration;
    }

    private function newTicketCode(): string
    {
        do {
            $code = 'GTAV-'.Str::upper(Str::random(8));
        } while (Registration::where('ticket_code', $code)->exists());

        return $code;
    }
}
