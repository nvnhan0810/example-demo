<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Cart\Domain\Cart\Ports\CartRepository;
use Modules\Cart\Infrastructure\Persistence\SessionCartRepository;
use Modules\Catalog\Domain\Product\Ports\ProductRepository;
use Modules\Catalog\Infrastructure\Persistence\EloquentProductRepository;
use Modules\Order\Domain\Order\Ports\OrderRepository;
use Modules\Order\Infrastructure\Persistence\EloquentOrderRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(CartRepository::class, SessionCartRepository::class);
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
