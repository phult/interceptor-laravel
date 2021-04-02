<?php 
namespace Megaads\Interceptor;

use Illuminate\Routing\RoutingServiceProvider;
use Megaads\Interceptor\Router;

class InterceptorServiceProvider extends RoutingServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['interceptor:clear'] = $this->app->share(function ($app) {
        });
        $this->commands(
            'interceptor:clear'
        );
    }

    /**
     * Boot the service provider. We bind our router to the application
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/Routes.php';
        // $this->app['router'] = $this->app->share(function ($app) {
            // var_dump($app);die;
        //     return new \Megaads\Interceptor\Filters($app);
        // });
        // $this->app['router']->registerFilterCacheGet();
        // $this->app['router']->registerFilterCacheSet();
        parent::boot();
    }

}
