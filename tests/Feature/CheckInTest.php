<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesEvents;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use CreatesEvents, RefreshDatabase;

    public function test_first_scan_is_authorized_and_second_is_already_used(): void
    {
        $event = $this->event();
        $staff = $this->user(Role::GateStaff);
        $this->assignGateStaff($event, $staff);
        $registration = $this->registration($event);

        $this->actingAs($staff)->postJson(route('checkin.scan', $event), ['code' => $registration->ticket_code])
            ->assertOk()->assertJson(['status' => 'authorized', 'count' => ['entered' => 1]]);

        $this->actingAs($staff)->postJson(route('checkin.scan', $event), ['code' => strtolower($registration->ticket_code)])
            ->assertOk()->assertJson(['status' => 'already_used']);

        $this->assertSame(1, Attendance::count());
    }

    public function test_unpaid_ticket_is_denied(): void
    {
        $event = $this->event();
        $staff = $this->user(Role::GateStaff);
        $this->assignGateStaff($event, $staff);
        $registration = $this->registration($event, PaymentStatus::Pending);

        $this->actingAs($staff)->postJson(route('checkin.scan', $event), ['code' => $registration->ticket_code])
            ->assertJson(['status' => 'denied']);

        $this->assertSame(0, Attendance::count());
    }

    public function test_ticket_for_another_event_is_denied(): void
    {
        $event = $this->event();
        $other = $this->event();
        $staff = $this->user(Role::GateStaff);
        $this->assignGateStaff($event, $staff);
        $registration = $this->registration($other);

        $this->actingAs($staff)->postJson(route('checkin.scan', $event), ['code' => $registration->ticket_code])
            ->assertJson(['status' => 'denied']);
    }

    public function test_scan_is_denied_outside_the_event_window(): void
    {
        $event = $this->event([
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(4),
            'registration_cutoff' => now()->addDays(2),
        ]);
        $staff = $this->user(Role::GateStaff);
        $this->assignGateStaff($event, $staff);
        $registration = $this->registration($event);

        $this->actingAs($staff)->postJson(route('checkin.scan', $event), ['code' => $registration->ticket_code])
            ->assertJson(['status' => 'denied']);
    }

    public function test_unassigned_gate_staff_cannot_scan(): void
    {
        $event = $this->event();
        $registration = $this->registration($event);

        $this->actingAs($this->user(Role::GateStaff))
            ->postJson(route('checkin.scan', $event), ['code' => $registration->ticket_code])
            ->assertForbidden();
    }

    public function test_attendee_cannot_use_scanner(): void
    {
        $this->actingAs($this->user())->get(route('checkin.index'))->assertForbidden();
    }

    public function test_manual_search_finds_attendee_by_name(): void
    {
        $event = $this->event();
        $staff = $this->user(Role::GateStaff);
        $this->assignGateStaff($event, $staff);
        $registration = $this->registration($event);

        $this->actingAs($staff)->getJson(route('checkin.search', [$event, 'q' => substr($registration->user->name, 0, 4)]))
            ->assertOk()->assertJsonFragment(['code' => $registration->ticket_code]);
    }
}
