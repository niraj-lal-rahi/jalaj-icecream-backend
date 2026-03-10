<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\SaleRepository as SaleRepositoryContract;
use App\Repositories\Contracts\SellerRepository as SellerRepositoryContract;
use App\Repositories\Contracts\ItemRepository as ItemRepositoryContract;
use App\Repositories\Eloquent\SaleRepository;
use App\Repositories\Eloquent\SellerRepository;
use App\Repositories\Eloquent\ItemRepository;

class AppServiceProvider extends ServiceProvider
{
    /** Bind repository interfaces to Eloquent implementations (enables DI) */
    public function register(): void
    {
        // Bind repository interfaces to Eloquent implementations
        $this->app->bind(
            SaleRepositoryContract::class,
            SaleRepository::class,
        );

        $this->app->bind(
            SellerRepositoryContract::class,
            SellerRepository::class,
        );

        $this->app->bind(
            ItemRepositoryContract::class,
            ItemRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
