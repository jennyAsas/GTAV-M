<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Attendee = 'attendee';
    case Speaker = 'speaker';
    case GateStaff = 'gate_staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Global Administrator',
            self::Manager => 'Regional Chapter Manager',
            self::Attendee => 'Corporate Attendee',
            self::Speaker => 'Guest Speaker',
            self::GateStaff => 'Venue Gate Staff',
        };
    }

    /** Roles a visitor may pick when registering an account (FR-01, D9). */
    public static function selfRegistrable(): array
    {
        return [self::Attendee->value, self::Speaker->value];
    }
}
