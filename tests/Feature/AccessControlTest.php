<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesEvents;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use CreatesEvents, RefreshDatabase;

    private function eventPayload(int $chapterId): array
    {
        return [
            'chapter_id' => $chapterId,
            'name' => 'Export Workshop',
            'type' => 'workshop',
            'venue' => 'Innovation Lab',
            'starts_at' => now()->addDays(10)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(10)->addHours(3)->format('Y-m-d\TH:i'),
            'registration_cutoff' => now()->addDays(9)->format('Y-m-d\TH:i'),
            'capacity' => 50,
            'ticket_price' => 650,
        ];
    }

    public function test_manager_creates_event_in_own_chapter(): void
    {
        $manager = $this->user(Role::Manager);
        $chapter = $this->chapter($manager);

        $this->actingAs($manager)->post(route('events.store'), $this->eventPayload($chapter->id))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('events', ['name' => 'Export Workshop', 'chapter_id' => $chapter->id]);
    }

    public function test_manager_cannot_create_event_in_another_chapter(): void
    {
        $manager = $this->user(Role::Manager);
        $this->chapter($manager);
        $other = $this->chapter($this->user(Role::Manager));

        $this->actingAs($manager)->post(route('events.store'), $this->eventPayload($other->id))->assertForbidden();
    }

    public function test_manager_cannot_edit_or_review_another_chapters_event(): void
    {
        $manager = $this->user(Role::Manager);
        $event = $this->event([], $this->chapter($this->user(Role::Manager)));
        $registration = $this->registration($event, PaymentStatus::Pending);

        $this->actingAs($manager)->get(route('events.edit', $event))->assertForbidden();
        $this->actingAs($manager)->post(route('registrations.paid', $registration))->assertForbidden();
        $this->actingAs($manager)->get(route('events.registrations', $event))->assertForbidden();
    }

    public function test_event_end_must_follow_start(): void
    {
        $admin = $this->user(Role::Admin);
        $payload = $this->eventPayload($this->chapter()->id);
        $payload['ends_at'] = now()->addDays(9)->format('Y-m-d\TH:i');

        $this->actingAs($admin)->post(route('events.store'), $payload)->assertSessionHasErrors('ends_at');
    }

    public function test_attendee_cannot_create_events_or_manage_chapters(): void
    {
        $attendee = $this->user();

        $this->actingAs($attendee)->get(route('events.create'))->assertForbidden();
        $this->actingAs($attendee)->get(route('chapters.index'))->assertForbidden();
        $this->actingAs($attendee)->get(route('admin.users'))->assertForbidden();
    }

    public function test_admin_creates_gate_staff_account(): void
    {
        $this->actingAs($this->user(Role::Admin))->post(route('admin.users.store'), [
            'name' => 'Gate One',
            'email' => 'gate1@example.com',
            'password' => 'gatepass123',
            'role' => 'gate_staff',
        ])->assertSessionHasNoErrors();

        $this->assertSame(Role::GateStaff, User::firstWhere('email', 'gate1@example.com')->role);
    }

    public function test_dashboard_revenue_counts_paid_tickets_only(): void
    {
        $manager = $this->user(Role::Manager);
        $event = $this->event(['ticket_price' => 500], $this->chapter($manager));
        $this->registration($event, PaymentStatus::Paid);
        $this->registration($event, PaymentStatus::Paid);
        $this->registration($event, PaymentStatus::Pending);

        $this->actingAs($manager)->get(route('dashboard'))->assertOk()->assertSee('₱1,000.00');
    }
}
