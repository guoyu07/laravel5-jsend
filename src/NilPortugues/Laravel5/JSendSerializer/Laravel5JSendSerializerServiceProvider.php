<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 8/15/15
 * Time: 5:45 PM.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Laravel5\JSendSerializer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use NilPortugues\Api\JSend\JSendTransformer;
use NilPortugues\Api\Mapping\Mapping;
use NilPortugues\Laravel5\JSendSerializer\Mapper\Mapper;
use ReflectionClass;

class Laravel5JSendSerializerServiceProvider extends ServiceProvider
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
        $this->app->singleton(\NilPortugues\Laravel5\JSendSerializer\JSendSerializer::class, function ($app) {
                $mapping = $app['config']->get('jsend');
                $key = md5(json_encode($mapping));

                return Cache::rememberForever($key, function () use ($mapping) {
                    return new JSendSerializer(new JSendTransformer(self::parseRoutes(new Mapper($mapping))));
                });
            });
    }


    /**
     * @param Mapper $mapper
     *
     * @return Mapper
     */
    private static function parseRoutes(Mapper $mapper)
    {
        foreach ($mapper->getClassMap() as &$mapping) {

            $mappingClass = new \ReflectionClass($mapping);

            self::setUrlWithReflection($mapping, $mappingClass, 'resourceUrlPattern');
            self::setUrlWithReflection($mapping, $mappingClass, 'selfUrl');
            $mappingProperty = $mappingClass->getProperty('otherUrls');
            $mappingProperty->setAccessible(true);

            $otherUrls = (array) $mappingProperty->getValue($mapping);
            if(!empty($otherUrls)) {
                foreach ($otherUrls as &$url) {
                    $url = urldecode(route($url));
                }
            }
            $mappingProperty->setValue($mapping, $otherUrls);

        }

        return $mapper;
    }


    /**
     * @param Mapping         $mapping
     * @param ReflectionClass $mappingClass
     * @param string          $property
     */
    private static function setUrlWithReflection(Mapping $mapping, ReflectionClass $mappingClass, $property)
    {
        $mappingProperty = $mappingClass->getProperty($property);
        $mappingProperty->setAccessible(true);
        $value = $mappingProperty->getValue($mapping);

        if(!empty($value)) {
            $value = urldecode(route($value));
            $mappingProperty->setValue($mapping, $value);
        }
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
