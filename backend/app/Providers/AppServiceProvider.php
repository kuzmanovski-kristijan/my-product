<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Policies\AdminOnlyPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Category::class, AdminOnlyPolicy::class);
        Gate::policy(Product::class, AdminOnlyPolicy::class);
        Gate::policy(ProductVariant::class, AdminOnlyPolicy::class);
        Gate::policy(ProductImage::class, AdminOnlyPolicy::class);
        Gate::policy(Order::class, AdminOnlyPolicy::class);
        Gate::policy(Store::class, AdminOnlyPolicy::class);
        Gate::policy(Appointment::class, AdminOnlyPolicy::class);
    }
}
