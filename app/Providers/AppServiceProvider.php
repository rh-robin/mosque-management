<?php

namespace App\Providers;

use App\Models\User;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\SubscriptionItem;
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
        Cashier::useCustomerModel(User::class);
        Cashier::useSubscriptionItemModel(SubscriptionItem::class);
    }
}
