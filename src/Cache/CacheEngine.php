<?php
namespace Megaads\Interceptor\Cache;

use Illuminate\Http\Response;
use Megaads\Interceptor\Cache\CacheStore;
use Megaads\Interceptor\Cache\CacheWorker;
use Megaads\Interceptor\Cache\RequestParser;

class CacheEngine
{
    protected $cacheStore;
    protected $requestParser;
    protected $cacheWorker;
    protected $requestParserData = [];
    public function __construct()
    {
        $this->cacheStore = new CacheStore();
        $this->requestParser = new RequestParser();
        $this->cacheWorker = new CacheWorker();
    }

    public function before($request, $response = null)
    {
        $this->requestParserData = $this->requestParser->parse($request);
        $this->requestParserData['cache-state'] = 'MISS';
        if (
            // check if cache engine is enabled for request
            array_key_exists('enable', $this->requestParserData) && $this->requestParserData['enable']
            // check if not is a request from interceptor
            && $request->header('Referer') !== 'interceptor-worker'
            // check if not is a clear-cache request
            && !array_key_exists('clear_cache', $request->query())
        ) {
            $cacheData = $this->cacheStore->getResponseData($this->requestParserData);
            if ($cacheData != null) {
                /** CACHE HIT **/
                $this->cacheStore->summary('cache-hit');
                $response = new Response();
                // async refresh cache if it's out-of-date
                if ($this->cacheStore->isOutOfDateResponse($this->requestParserData) === true) {
                    /** CACHE HIT EXPIRED **/
                    $this->cacheStore->summary('cache-hit-expired');
                    $this->cacheWorker->refreshCache($this->requestParserData['url'], [$this->requestParserData['device']], true);
                }
                $this->cacheStore->saveLastActiveTimeURL($this->requestParserData);
                $cacheTime = $this->cacheStore->getResponseCacheTime($this->requestParserData);
                $this->requestParserData['cache-state'] = 'HIT';
                $response->header('Served-From', 'interceptor');
                $response->header('Interceptor-Meta-Data', json_encode($this->requestParserData));
                $response->header('Interceptor-Refresh-Time', date('d M Y H:i:s', $cacheTime));
                return $response->setContent($cacheData);
            } else {
                /** CACHE MISS **/
                $this->cacheStore->summary('cache-miss');
            }
        }
    }

    public function after($request, $response = null)
    {        
        if (array_key_exists('enable', $this->requestParserData)
            && array_key_exists('cache-state', $this->requestParserData)
            && $this->requestParserData['enable']) {
            if ($this->requestParserData['cache-state'] !== 'HIT') {
                //check status code
                $availableStatusCode = true;
                $cachedStatuses = \Config::get('interceptor.statuses', []);
                $phpStatusCode = http_response_code();
                $availableStatusCode = in_array($phpStatusCode, $cachedStatuses);
                if ($availableStatusCode && method_exists($response, 'getStatusCode')) {
                    $laravel4StatusCode = $response->getStatusCode();
                    $availableStatusCode = in_array($laravel4StatusCode, $cachedStatuses);
                }
                if ($availableStatusCode && method_exists($response, 'status')) {
                    $laravel5StatusCode = $response->status();
                    $availableStatusCode = in_array($laravel5StatusCode, $cachedStatuses);
                }
                if ($availableStatusCode) {
                    try {
                        $this->cacheStore->saveResponseData($response, $this->requestParserData);
                        if ($request->header('Referer') !== 'interceptor-worker') {
                            $this->cacheStore->saveLastActiveTimeURL($this->requestParserData);
                        }
                    } catch (\Throwable $th) {}
                }
            } else {
                $this->cacheStore->saveLastActiveTimeURL($this->requestParserData);
            }
        }
    }
}
