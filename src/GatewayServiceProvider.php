<?php

namespace Mozakar\Gateway;

use Illuminate\Support\Facades\File;
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
        if (version_compare(\Illuminate\Foundation\Application::VERSION, '5.0', '<')) {
            $provider = 'Mozakar\Gateway\GatewayServiceProviderLaravel4';
        }elseif (version_compare(\Illuminate\Foundation\Application::VERSION, '5.0', '>=') && version_compare(\Illuminate\Foundation\Application::VERSION, '6.0', '<')) {
            $provider = 'Mozakar\Gateway\GatewayServiceProviderLaravel5';
        }
        elseif (version_compare(\Illuminate\Foundation\Application::VERSION, '6.0', '>=') && version_compare(\Illuminate\Foundation\Application::VERSION, '7.0', '<')) {
            $provider = 'Mozakar\Gateway\GatewayServiceProviderLaravel6';
        }
        elseif (version_compare(\Illuminate\Foundation\Application::VERSION, '7.0', '>=') && version_compare(\Illuminate\Foundation\Application::VERSION, '8.0', '<')) {
            $provider = 'Mozakar\Gateway\GatewayServiceProviderLaravel7';
        }
        elseif (version_compare(\Illuminate\Foundation\Application::VERSION, '8.0', '>=') && version_compare(\Illuminate\Foundation\Application::VERSION, '9.0', '<')) {
            $provider = 'Mozakar\Gateway\GatewayServiceProviderLaravel8';
        }
        elseif (version_compare(\Illuminate\Foundation\Application::VERSION, '9.0', '>=') && version_compare(\Illuminate\Foundation\Application::VERSION, '9.0', '<')) {
            $provider = 'Mozakar\Gateway\GatewayServiceProviderLaravel9';
        }
        else {
            $provider = 'Mozakar\Gateway\GatewayServiceProviderLaravel10';
        }

        return new $provider($this->app);
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
