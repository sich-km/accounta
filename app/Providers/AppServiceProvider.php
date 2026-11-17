<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;

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
        Passport::authorizationView('mcp.authorize');

        RateLimiter::for('mcp-oauth-register', function (Request $request): Limit {
            if ($request->isMethod('POST') && $request->is('oauth/register')) {
                return Limit::perMinute(10)->by($request->ip());
            }

            return Limit::none();
        });

        RateLimiter::for('mcp', function (Request $request): Limit {
            $user = $request->user('api');
            $token = $user instanceof User ? $user->currentAccessToken() : null;
            $tokenId = $token instanceof AccessToken ? $token->oauth_access_token_id : null;

            return Limit::perMinute(60)->by(match (true) {
                is_string($tokenId) && $tokenId !== '' => 'token:'.$tokenId,
                $user instanceof User => 'user:'.$user->getAuthIdentifier(),
                default => 'ip:'.$request->ip(),
            });
        });

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
