<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Arrival;
use App\Models\Product;
use App\Models\User;
use App\Policies\ArrivalPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        Route::bind('product', function (string $value): Product {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return Product::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('arrival', function (string $value): Arrival {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return Arrival::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Arrival::class, ArrivalPolicy::class);

        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, function (User $user) use ($permission): bool {
                return $user->hasPermission($permission);
            });
        }
    }
}
