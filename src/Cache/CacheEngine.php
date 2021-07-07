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

    public function before($request, $response = null)
    {
        $this->requestParserData = $this->requestParser->parse($request);
        $this->requestParserData['cache-state'] = 'MISS';
        if (array_key_exists('enable', $this->requestParserData)
            && $this->requestParserData['enable']
            && $this->cacheStore->isOutOfDateResponse($this->requestParserData) === false
            && $request->header('Referer') !== 'interceptor-worker') {
            $cacheData = $this->cacheStore->getResponseData($this->requestParserData);
            if ($cacheData != null) {
                $this->cacheStore->saveLastActiveTimeURL($this->requestParserData);
                $cacheTime = $this->cacheStore->getResponseCacheTime($this->requestParserData);
                $this->requestParserData['cache-state'] = 'HIT';
                $response = new Response();
                $response->header('Served-From', 'interceptor');
                $response->header('Interceptor-Refresh-Time', date('d M Y H:i:s', $cacheTime));
                $response->header('Interceptor-URL', $this->requestParserData['url']);
                return $response->setContent($cacheData);
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
                $cachedStatuses = \Config::get('interceptor.statuses', []);
                if (in_array(http_response_code(), $cachedStatuses)) {
                    $this->cacheStore->saveResponseData($response, $this->requestParserData);
                    $this->cacheStore->saveLastActiveTimeURL($this->requestParserData);
                }
            } else {
                $this->cacheStore->saveLastActiveTimeURL($this->requestParserData);
            }
        }
    }
}
