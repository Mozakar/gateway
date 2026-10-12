<?php

namespace Mozakar\Gateway;

use Illuminate\Support\ServiceProvider;

class GatewayServiceProviderLaravel8 extends ServiceProvider
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

        // php artisan vendor:publish --provider=Mozakar\Gateway\GatewayServiceProviderLaravel8 --tag=config
        $this->publishes([
            $config => config_path('gateway.php'),
        ], 'config');

        $migrationPaths = [
            $migrations => database_path('migrations'),
        ];

        // Laravel 11+ rewrites migration timestamps when publishing.
        if (method_exists($this, 'publishesMigrations')) {
            $this->publishesMigrations($migrationPaths, 'migrations');
        } else {
            $this->publishes($migrationPaths, 'migrations');
        }

        $this->loadViewsFrom($views, 'gateway');

        // php artisan vendor:publish --provider=Mozakar\Gateway\GatewayServiceProviderLaravel8 --tag=views
        $this->publishes([
            $views => resource_path('views/vendor/gateway'),
        ], 'views');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/gateway.php', 'gateway');

		$this->app->singleton('gateway', function () {
			return new GatewayResolver();
		});
	}
}
