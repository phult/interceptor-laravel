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
        if (method_exists($this->app['router'], 'aliasMiddleware')) {
            // Laravel >5.4
            $this->app->router->aliasMiddleware('interceptor', \Megaads\Interceptor\Middlewares\Caching::class);
        } else if (method_exists($this->app['router'], 'middleware')) {
            // Laravel 5.2.*
            $this->app->router->middleware('interceptor', \Megaads\Interceptor\Middlewares\Caching::class);
        } else {
            // Laravel 4.*
            include __DIR__ . '/Routes.php';
            parent::boot();
        }       
    }

}
