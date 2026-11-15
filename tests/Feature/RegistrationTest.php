<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesEvents;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use CreatesEvents, RefreshDatabase;

    public function test_attendee_registration_is_pending_with_a_ticket_code(): void
    {
        $event = $this->event();
        $attendee = $this->user();

        $this->actingAs($attendee)->post(route('events.register', $event))->assertSessionHasNoErrors();

        $registration = Registration::firstWhere('user_id', $attendee->id);
        $this->assertSame(PaymentStatus::Pending, $registration->payment_status);
        $this->assertStringStartsWith('GTAV-', $registration->ticket_code);
    }

    public function test_free_event_registration_is_paid_immediately(): void
    {
        $event = $this->event(['ticket_price' => 0]);
        $attendee = $this->user();

        $this->actingAs($attendee)->post(route('events.register', $event));

        $this->assertTrue(Registration::firstWhere('user_id', $attendee->id)->isVerified());
    }

    public function test_attendee_cannot_register_twice(): void
    {
        $event = $this->event();
        $attendee = $this->user();

        $this->actingAs($attendee)->post(route('events.register', $event));
        $this->actingAs($attendee)->post(route('events.register', $event))->assertSessionHasErrors('event');

        $this->assertSame(1, Registration::where('user_id', $attendee->id)->count());
    }

    public function test_full_event_refuses_registration(): void
    {
        $event = $this->event(['capacity' => 1]);
        $this->registration($event, PaymentStatus::Pending);

        $this->actingAs($this->user())->post(route('events.register', $event))->assertSessionHasErrors('event');

        $this->assertSame(1, $event->registrations()->count());
    }

    public function test_registration_after_cutoff_is_refused(): void
    {
        $event = $this->event(['registration_cutoff' => now()->subMinute()]);

        $this->actingAs($this->user())->post(route('events.register', $event))->assertSessionHasErrors('event');

        $this->assertSame(0, $event->registrations()->count());
    }

    public function test_non_attendee_cannot_register(): void
    {
        $event = $this->event();

        $this->actingAs($this->user(Role::Speaker))->post(route('events.register', $event))->assertSessionHasErrors('event');

        $this->assertSame(0, $event->registrations()->count());
    }

    public function test_rejected_registration_frees_its_seat(): void
    {
        $manager = $this->user(Role::Manager);
        $event = $this->event(['capacity' => 1], $this->chapter($manager));
        $first = $this->registration($event, PaymentStatus::Pending);

        $this->actingAs($manager)->post(route('registrations.reject', $first));
        $this->actingAs($this->user())->post(route('events.register', $event))->assertSessionHasNoErrors();

        $this->assertSame(1, $event->seatsTaken());
    }

    public function test_manager_marks_payment_paid(): void
    {
        $manager = $this->user(Role::Manager);
        $event = $this->event([], $this->chapter($manager));
        $registration = $this->registration($event, PaymentStatus::Pending);

        $this->actingAs($manager)->post(route('registrations.paid', $registration))->assertSessionHasNoErrors();

        $this->assertTrue($registration->fresh()->isVerified());
        $this->assertSame($manager->id, $registration->fresh()->verified_by);
    }
}
