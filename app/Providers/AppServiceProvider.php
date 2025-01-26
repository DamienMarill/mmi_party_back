<?php

namespace App\Providers;

use App\Models\CardTemplate;
use App\Observers\CardTemplateObserver;
use App\Providers\Faker\ImageProvider;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Generator::class, function () {
            $faker = Factory::create();
            $faker->addProvider(new ImageProvider($faker));
            return $faker;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CardTemplate::observe(CardTemplateObserver::class);
    }
}
