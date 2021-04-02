<?php
namespace Megaads\Interceptor;

// use Illuminate\Http\Request;
// use Illuminate\Http\Response;
// use Illuminate\Routing\Route;
use Megaads\Interceptor\Cache\CacheEngine;

$cacheEngine = new CacheEngine();
\Route::filter('interceptor-before', function ($route, $request, $response = null) use ($cacheEngine) {
    return $cacheEngine->before($route, $request, $response);
});

\Route::filter('interceptor-after', function ($route, $request, $response = null) use ($cacheEngine) {
    $cacheEngine->after($route, $request, $response);
});
