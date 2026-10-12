<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\CatalogValuePolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * The owner is granted every ability, EXCEPT the ones no role may be given
     * blindly. Those fall through to their policy.
     *
     * Two kinds qualify. The owner must not be able to block, demote or delete
     * itself, which would be unrecoverable. And a catalog value the code
     * branches on must not be retired by anyone, or a financial path would be
     * left with nothing to match.
     *
     * Gate::before only receives the ability name, never the model, so the
     * deferral is by name and each policy declares its own.
     */
    protected function configureAuthorization(): void
    {
        $deferred = [
            ...UserPolicy::OWNER_PROTECTED_ABILITIES,
            ...CatalogValuePolicy::SYSTEM_PROTECTED_ABILITIES,
        ];

        Gate::before(function (User $user, string $ability) use ($deferred): ?bool {
            if (in_array($ability, $deferred, true)) {
                return null;
            }

            return $user->isOwner() ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
