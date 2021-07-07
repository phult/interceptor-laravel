<?php

namespace Megaads\Interceptor\Middlewares;

use Closure;
use Megaads\Interceptor\Cache\CacheEngine;

class Caching
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $cacheEngine = new CacheEngine();
        $response = $cacheEngine->before($request);
        if (!$response) {
            $response = $next($request);
            $cacheEngine->after($request, $response);
        }
        return $response;
    }

}