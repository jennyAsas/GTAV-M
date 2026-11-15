<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'company', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function managedChapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'manager_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === Role::Manager;
    }

    public function isAttendee(): bool
    {
        return $this->role === Role::Attendee;
    }

    public function isSpeaker(): bool
    {
        return $this->role === Role::Speaker;
    }

    public function isGateStaff(): bool
    {
        return $this->role === Role::GateStaff;
    }

    /** Admins manage everything; a manager manages events of the chapter they are assigned to (D6). */
    public function canManageEvent(Event $event): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isManager()
            && $event->chapter()->where('manager_id', $this->id)->exists();
    }

    public function isAssignedAs(Event $event, string $assignment): bool
    {
        return EventAssignment::where('event_id', $event->id)
            ->where('user_id', $this->id)
            ->where('assignment', $assignment)
            ->exists();
    }
}
