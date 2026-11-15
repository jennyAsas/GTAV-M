<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the first administrator from ADMIN_EMAIL / ADMIN_PASSWORD.
     * There is no default password: if either value is empty, nothing is created.
     */
    public function run(): void
    {
        $email = config('gtav.admin.email');
        $password = config('gtav.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set; no administrator created.');

            return;
        }

        // Created once. A later deploy never resets an existing administrator's password.
        User::firstOrCreate(
            ['email' => $email],
            ['name' => config('gtav.admin.name'), 'password' => $password, 'role' => Role::Admin],
        );
    }
}
