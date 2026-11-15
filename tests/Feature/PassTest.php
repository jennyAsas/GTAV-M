<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesEvents;
use Tests\TestCase;

class PassTest extends TestCase
{
    use CreatesEvents, RefreshDatabase;

    public function test_owner_of_paid_registration_sees_pass_with_qr(): void
    {
        $registration = $this->registration($this->event());

        $this->actingAs($registration->user)
            ->get(route('registrations.pass', $registration))
            ->assertOk()
            ->assertSee($registration->ticket_code)
            ->assertSee('<svg', false);
    }

    public function test_pending_registration_has_no_pass(): void
    {
        $registration = $this->registration($this->event(), PaymentStatus::Pending);

        $this->actingAs($registration->user)->get(route('registrations.pass', $registration))->assertForbidden();
    }

    public function test_other_user_cannot_open_someone_elses_pass(): void
    {
        $registration = $this->registration($this->event());

        $this->actingAs($this->user())->get(route('registrations.pass', $registration))->assertForbidden();
    }
}
