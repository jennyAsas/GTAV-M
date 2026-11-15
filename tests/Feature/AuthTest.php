<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $role): array
    {
        return [
            'name' => 'Ana Cruz',
            'company' => 'Northstar Trading',
            'email' => 'ana@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => $role,
        ];
    }

    public function test_attendee_can_register_and_is_logged_in(): void
    {
        $this->post('/register', $this->payload('attendee'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertSame(Role::Attendee, User::firstWhere('email', 'ana@example.com')->role);
    }

    public static function staffRoles(): array
    {
        return ['admin' => ['admin'], 'manager' => ['manager'], 'gate staff' => ['gate_staff']];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_roles_cannot_be_self_registered(string $role): void
    {
        $this->post('/register', $this->payload($role))->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_password_is_stored_hashed(): void
    {
        $this->post('/register', $this->payload('attendee'));

        $this->assertNotSame('secret123', User::firstWhere('email', 'ana@example.com')->password);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
