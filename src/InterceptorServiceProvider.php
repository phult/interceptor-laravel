<?php
namespace Megaads\Interceptor;

use Illuminate\Routing\RoutingServiceProvider;
use Megaads\Interceptor\Commands\FlushCacheCommand;
use Megaads\Interceptor\Commands\GarbageCacheCommand;
use Megaads\Interceptor\Commands\MonitorCacheCommand;
use Megaads\Interceptor\Commands\RefreshCacheCommand;
use Megaads\Interceptor\Commands\RemoveCacheCommand;
use Megaads\Interceptor\Router;

class InterceptorServiceProvider extends RoutingServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    protected $commands = [
        FlushCacheCommand::class,
        RefreshCacheCommand::class,
        RemoveCacheCommand::class,
        MonitorCacheCommand::class,
        GarbageCacheCommand::class,
    ];
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }

    /**
     * Boot the service provider. We bind our router to the application
     *
     * @return void
     */
    public function boot()
    {
        if (method_exists($this->app, 'aliasMiddleware')) {
            $this->app['router']->aliasMiddleware('interceptor', \Megaads\Interceptor\Middlewares\Caching::class);
        } else {
            include __DIR__ . '/Routes.php';
            parent::boot();
        }        
    }

}
