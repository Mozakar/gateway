<?php

namespace Mozakar\Gateway;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class GatewayServiceProviderLaravel10 extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        $config = __DIR__ . '/../config/gateway.php';
        $migrations = __DIR__ . '/../migrations/';
        $views = __DIR__ . '/../views/';

        //php artisan vendor:publish --provider=Mozakar\Gateway\GatewayServiceProvider --tag=config
        $this->publishes([
            $config => config_path('gateway.php'),
        ]);
		
        // php artisan vendor:publish --provider=Mozakar\Gateway\GatewayServiceProvider --tag=migrations
        $this->publishes([
            $migrations => database_path('migrations')
        ], 'migrations');

		
        $this->loadViewsFrom($views, 'gateway');

        // php artisan vendor:publish --provider=Mozakar\Gateway\GatewayServiceProvider --tag=views
        $this->publishes([
            $views => resource_path('views/vendor/gateway'),
        ], 'views');

        //$this->mergeConfigFrom( $config,'gateway')
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('gateway', function () {
			return new GatewayResolver();
		});

	}
}
