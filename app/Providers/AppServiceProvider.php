<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('profile.show', function (ViewInstance $view): void {
            $user = $view->getData()['user'] ?? null;

            if ($user instanceof User) {
                $user->loadMissing(['company', 'organization']);
                $view->with('company', $user->company);
                $view->with('organization', $user->organization);
            }
        });
    }
}
