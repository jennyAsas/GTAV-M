<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    /** FR-17: only the owner of a verified (paid) registration may open the pass. */
    public function viewPass(User $user, Registration $registration): bool
    {
        return $registration->user_id === $user->id && $registration->isVerified();
    }

    public function view(User $user, Registration $registration): bool
    {
        return $registration->user_id === $user->id;
    }

    /** FR-14: marking payments paid or rejected. */
    public function review(User $user, Registration $registration): bool
    {
        return $user->canManageEvent($registration->event);
    }
}
