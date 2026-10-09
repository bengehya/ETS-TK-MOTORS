<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Arrival;
use App\Models\CashDeclaration;
use App\Models\CustomerRequest;
use App\Models\Expense;
use App\Models\Invitation;
use App\Models\Product;
use App\Models\Rental;
use App\Models\RestockSuggestion;
use App\Models\Sale;
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

        Vite::createAssetPathsUsing(function (string $path): string {
            return '/'.ltrim($path, '/');
        });

        Route::bind('product', function (string $value): Product {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return Product::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('invitation', function (string $value): Invitation {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return Invitation::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('member', function (string $value): User {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return User::query()
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

        Route::bind('sale', function (string $value): Sale {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return Sale::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('expense', function (string $value): Expense {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return Expense::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('customerRequest', function (string $value): CustomerRequest {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return CustomerRequest::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('restockSuggestion', function (string $value): RestockSuggestion {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return RestockSuggestion::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('cashDeclaration', function (string $value): CashDeclaration {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return CashDeclaration::query()
                ->where('organization_id', $user->organization_id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('rental', function (string $value): Rental {
            $user = request()->user();
            abort_unless($user !== null, 401);

            return Rental::query()
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
