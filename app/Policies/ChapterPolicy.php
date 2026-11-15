<?php

namespace App\Policies;

use App\Models\User;

class ChapterPolicy
{
    /** FR-04/05: only administrators create and update chapters and assign managers. */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
