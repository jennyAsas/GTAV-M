<?php

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_only_attendee_and_speaker_can_self_register(): void
    {
        $this->assertSame(['attendee', 'speaker'], Role::selfRegistrable());
    }

    public function test_every_role_has_a_label(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertNotSame('', $role->label());
        }
    }
}
