<?php

namespace App\Providers;

use App\Modules\User\Commands\CleanExpiredTemporaryUsersCommand;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Repositories\UserRepositoryInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->commands([
            CleanExpiredTemporaryUsersCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configura os rate limiters da aplicação.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            /** @var \Illuminate\Contracts\Auth\Authenticatable|null $user */
            $user = $request->user();

            return Limit::perMinute(60)->by($user?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
