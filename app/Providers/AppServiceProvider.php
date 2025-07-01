<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Faker\Factory as Faker;

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
        //
        $this->app->bind('doubleBarrelSurname', function () {
            $faker = Faker::create();
            $faker->addProvider(new \App\Providers\DoubleBarreledSurnameProvider($faker));
            return $faker;
        });
    }
}
