<?php
namespace Megaads\Interceptor\Cache;

use Illuminate\Http\Response;
use Megaads\Interceptor\Cache\RequestParser;

class CacheEngine
{
    protected $requestParser;
    protected $requestParserData = [];
    const CACHE_TAGS_SAMPLE = ['appName', 'device'];
    public function __construct()
    {
        $this->requestParser = new RequestParser();
    }

    public function before($route, $request, $response = null)
    {
        $this->requestParserData = $this->requestParser->parse($request);
        if ($this->requestParserData['enable']) {
            $cacheData = $this->getCacheData($this->requestParserData);
            if ($cacheData != null) {
                $this->requestParserData['cache-state'] = 'HIT';
                $response = new Response();
                $response->header('Served-From', 'interceptor');
                return $response->setContent($cacheData);
            } else {
                $this->requestParserData['cache-state'] = 'MISS';
            }
        }
    }

    public function after($route, $request, $response = null)
    {
        if ($this->requestParserData['enable']
            && $this->requestParserData['cache-state'] == 'MISS') {
            //check status code
            $cachedStatuses = \Config::get('interceptor.statuses', []);
            if (in_array(http_response_code(), $cachedStatuses)) {
                $this->setCacheData($response, $this->requestParserData);
            }
        }
    }

    private function getCacheData($requestParserData)
    {
        $key = $this->buildCacheKey($requestParserData);
        $tags = $this->buildCacheTags($requestParserData);
        if (\Cache::tags($tags)->has($key)) {
            return \Cache::tags($tags)->get($key);
        }
    }

    private function setCacheData(\Illuminate\Http\Response $response, $requestParserData)
    {
        $key = $this->buildCacheKey($requestParserData);
        $tags = $this->buildCacheTags($requestParserData);
        // if (!\Cache::tags($tags)->has($key)) {
        \Cache::tags($tags)->put($key, $response->getContent(), $requestParserData['maxAge']);
        // }
    }
    private function buildCacheKey($requestParserData)
    {
        return $requestParserData['url'];
    }

    private function buildCacheTags($requestParserData)
    {
        $retval = [];
        for ($i = 0; $i < count(CacheEngine::CACHE_TAGS_SAMPLE); $i++) {
            $retval[] = $requestParserData[CacheEngine::CACHE_TAGS_SAMPLE[$i]];
        }
        return $retval;
    }
}
