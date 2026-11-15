<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public const AUTHORIZED = 'authorized';
    public const DENIED = 'denied';
    public const ALREADY_USED = 'already_used';

    /**
     * Validate a ticket code at the gate (FR-22). Returns status, a message and the attendee name.
     * A ticket can be checked in once: the registration row is locked and attendances.registration_id is unique.
     *
     * @return array{status: string, message: string, name: ?string}
     */
    public function scan(User $staff, Event $event, string $ticketCode): array
    {
        $code = strtoupper(trim($ticketCode));

        $registration = Registration::with('user')
            ->where('event_id', $event->id)
            ->where('ticket_code', $code)
            ->first();

        if (! $registration) {
            return $this->result(self::DENIED, 'Unknown ticket for this event.');
        }

        $name = $registration->user->name;

        if (! $registration->isVerified()) {
            return $this->result(self::DENIED, 'Payment is not verified for this ticket.', $name);
        }

        if (! $event->isScanWindowOpen()) {
            return $this->result(self::DENIED, 'Check-in is not open for this event right now.', $name);
        }

        try {
            return DB::transaction(function () use ($registration, $staff, $name) {
                $locked = Registration::query()->whereKey($registration->id)->lockForUpdate()->first();
                $existing = Attendance::where('registration_id', $locked->id)->first();

                if ($existing) {
                    return $this->result(
                        self::ALREADY_USED,
                        'Already checked in at '.$existing->checked_in_at->format('M j, g:i A').'.',
                        $name,
                    );
                }

                Attendance::create([
                    'registration_id' => $locked->id,
                    'checked_in_by' => $staff->id,
                    'checked_in_at' => now(),
                ]);

                return $this->result(self::AUTHORIZED, 'Entry authorized.', $name);
            });
        } catch (UniqueConstraintViolationException) {
            return $this->result(self::ALREADY_USED, 'Already checked in.', $name);
        }
    }

    /** FR-24: people checked in versus capacity. */
    public function liveCount(Event $event): array
    {
        $entered = Attendance::whereHas('registration', fn ($q) => $q->where('event_id', $event->id))->count();

        return ['entered' => $entered, 'capacity' => $event->capacity];
    }

    /** FR-23: manual search by name or ticket code, limited to this event. */
    public function search(Event $event, string $term)
    {
        // Escape LIKE wildcards so "%" or "_" in the input match literally.
        $term = addcslashes(trim($term), '\\%_');
        // Postgres LIKE is case-sensitive; ILIKE is not.
        $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return Registration::with(['user', 'attendance'])
            ->where('event_id', $event->id)
            ->where(function ($q) use ($term, $like) {
                $q->where('ticket_code', $like, '%'.strtoupper($term).'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', $like, '%'.$term.'%'));
            })
            ->limit(20)
            ->get();
    }

    private function result(string $status, string $message, ?string $name = null): array
    {
        return ['status' => $status, 'message' => $message, 'name' => $name];
    }
}
