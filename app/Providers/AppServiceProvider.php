<?php

namespace App\Providers;

use App\Models\Chapter;
use App\Models\Event;
use App\Models\Registration;
use App\Policies\ChapterPolicy;
use App\Policies\EventPolicy;
use App\Policies\RegistrationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Registration::class, RegistrationPolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);

        // Administrator-only actions (FR-03).
        Gate::define('administer', fn ($user) => $user->isAdmin());

        // Anyone who may use the scanner or the reports page.
        Gate::define('use-scanner', fn ($user) => $user->isAdmin() || $user->isManager() || $user->isGateStaff());
        Gate::define('view-reports', fn ($user) => $user->isAdmin() || $user->isManager());

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
