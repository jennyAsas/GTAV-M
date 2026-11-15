<?php

namespace Tests\Feature\Concerns;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Chapter;
use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesEvents
{
    protected function user(Role $role = Role::Attendee): User
    {
        return User::factory()->role($role)->create();
    }

    protected function chapter(?User $manager = null): Chapter
    {
        return Chapter::create(['name' => 'Chapter '.Str::random(6), 'manager_id' => $manager?->id]);
    }

    /** An event happening now (scan window open) whose registration is still open. */
    protected function event(array $overrides = [], ?Chapter $chapter = null): Event
    {
        $chapter ??= $this->chapter();

        return Event::create($overrides + [
            'chapter_id' => $chapter->id,
            'created_by' => $chapter->manager_id ?? $this->user(Role::Admin)->id,
            'name' => 'Trade Summit',
            'type' => 'summit',
            'venue' => 'Convention Center',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(6),
            'registration_cutoff' => now()->addHours(5),
            'capacity' => 10,
            'ticket_price' => 500,
        ]);
    }

    protected function registration(Event $event, PaymentStatus $status = PaymentStatus::Paid, ?User $attendee = null): Registration
    {
        return Registration::create([
            'event_id' => $event->id,
            'user_id' => ($attendee ?? $this->user())->id,
            'ticket_code' => 'GTAV-'.Str::upper(Str::random(8)),
            'payment_status' => $status,
            'paid_at' => $status === PaymentStatus::Paid ? now() : null,
            'registered_at' => now(),
        ]);
    }

    protected function assignGateStaff(Event $event, User $staff): void
    {
        EventAssignment::create(['event_id' => $event->id, 'user_id' => $staff->id, 'assignment' => 'gate_staff']);
    }
}
