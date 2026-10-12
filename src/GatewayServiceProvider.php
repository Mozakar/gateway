<?php

namespace Mozakar\Gateway;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class GatewayServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Actual provider
     *
     * @var \Illuminate\Support\ServiceProvider
     */
    protected $provider;

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->provider = $this->getProvider();
    }

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        if (method_exists($this->provider, 'boot')) {
            return $this->provider->boot();
        }
	}

    /**
     * Return ServiceProvider according to Laravel version
     *
     * @return \Intervention\Image\Provider\ProviderInterface
     */
    private function getProvider()
    {
        $version = Application::VERSION;

        $providers = [
            '13.0' => GatewayServiceProviderLaravel13::class,
            '12.0' => GatewayServiceProviderLaravel12::class,
            '11.0' => GatewayServiceProviderLaravel11::class,
            '10.0' => GatewayServiceProviderLaravel10::class,
            '9.0' => GatewayServiceProviderLaravel9::class,
            '8.0' => GatewayServiceProviderLaravel8::class,
            '7.0' => GatewayServiceProviderLaravel7::class,
            '6.0' => GatewayServiceProviderLaravel6::class,
            '5.0' => GatewayServiceProviderLaravel5::class,
        ];

        foreach ($providers as $minimum => $provider) {
            if (version_compare($version, $minimum, '>=')) {
                return new $provider($this->app);
            }
        }

        return new GatewayServiceProviderLaravel4($this->app);
    }

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
	    return $this->provider->register();
	}
}
