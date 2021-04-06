<?php
namespace Megaads\Interceptor\Cache;

use Illuminate\Http\Response;
use Megaads\Interceptor\Cache\CacheStore;
use Megaads\Interceptor\Cache\RequestParser;

class CacheEngine
{
    protected $cacheStore;
    protected $requestParser;
    protected $requestParserData = [];
    public function __construct()
    {
        $this->cacheStore = new CacheStore();
        $this->requestParser = new RequestParser();
    }

    public function before($route, $request, $response = null)
    {
        $this->requestParserData = $this->requestParser->parse($request);
        $this->requestParserData['cache-state'] = 'MISS';
        if ($this->requestParserData['enable']
            && $request->header('Referer') !== 'interceptor-worker') {
            $cacheData = $this->cacheStore->getResponseData($this->requestParserData);
            if ($cacheData != null) {
                $cacheTime = $this->cacheStore->getResponseCacheTime($this->requestParserData);
                $this->requestParserData['cache-state'] = 'HIT';
                $response = new Response();
                $response->header('Served-From', 'interceptor');
                $response->header('Interceptor-Fefresh-Time', date('d M Y H:i:s', $cacheTime));
                $response->header('Interceptor-URL', $this->requestParserData['url']);
                return $response->setContent($cacheData);
            }
        }
    }

    public function after($route, $request, $response = null)
    {
        if ($this->requestParserData['enable']
            && $this->requestParserData['cache-state'] !== 'HIT') {
            //check status code
            $cachedStatuses = \Config::get('interceptor.statuses', []);
            if (in_array(http_response_code(), $cachedStatuses)) {
                $this->cacheStore->saveResponseData($response, $this->requestParserData);
            }
        }
    }
}
