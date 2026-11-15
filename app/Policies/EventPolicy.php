<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /** FR-06/07/08/09/11/16: edit, assign staff, view registrants and reports for an event. */
    public function manage(User $user, Event $event): bool
    {
        return $user->canManageEvent($event);
    }

    /** FR-21/22/23/24: scan tickets for an event. Gate staff only for events they are assigned to (D5). */
    public function scan(User $user, Event $event): bool
    {
        return $user->canManageEvent($event)
            || ($user->isGateStaff() && $user->isAssignedAs($event, 'gate_staff'));
    }
}
