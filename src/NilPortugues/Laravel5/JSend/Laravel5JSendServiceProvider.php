<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 8/15/15
 * Time: 5:45 PM.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Laravel5\JSend;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use NilPortugues\Laravel5\JSend\Providers\Laravel51Provider;
use NilPortugues\Laravel5\JsonApi\Providers\Laravel52Provider;

class Laravel5JSendServiceProvider extends ServiceProvider
{
    const PATH = '/../../../config/jsend.php';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([__DIR__.self::PATH => config('jsend.php')]);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.self::PATH, 'jsend');

        $version = Application::VERSION;

        switch ($version) {
            case false !== strpos($version, '5.0.'):
            case false !== strpos($version, '5.1.'):
                $provider = new Laravel51Provider();
                break;
            case false !== strpos($version, '5.2.'):
                $provider = new Laravel52Provider();
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Laravel version %s is not supported. Please use the 5.1 for the time being', $version)
                );
                break;
        }

        $this->app->singleton(JsendSerializer::class, $provider->provider());
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['jsend'];
    }
}
