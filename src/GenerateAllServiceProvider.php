<?php
namespace Hizbul\Generators;

use Illuminate\Support\ServiceProvider;


class GenerateAllServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.hizbul.generate.all', function ($app) {
            return $app['Hizbul\Generators\GenerateResourceCommand'];
        });
        $this->commands('command.hizbul.generate.all');
    }

}