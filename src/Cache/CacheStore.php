<?php
namespace Megaads\Interceptor\Cache;

use Illuminate\Support\Facades\Redis;

class CacheStore
{
    const CACHE_TAGS_SAMPLE = ['appName', 'device'];
    protected $redis;
    protected $appName;
    public function __construct()
    {
        $this->redis = Redis::connection('cache');
        $this->appName = \Config::get('interceptor.appName', 'interceptor');
    }
    public function saveResponseData(\Illuminate\Http\Response $response, $requestParserData)
    {
        $time = time();
        $key = $this->buildCacheKey($requestParserData);
        // if (!\Cache::tags($tags)->has($key)) {
        $this->redis->set($key, $response->getContent());
        $this->redis->zadd('interceptor-cache-time', $time, $key);
        // }
        return $time;
    }

    public function getResponseData($requestParserData)
    {
        $key = $this->buildCacheKey($requestParserData);
        $retval = $this->redis->get($key);
        if ($retval != null) {
            return $retval;
        }
    }

    public function getResponseCacheTime($requestParserData)
    {
        $key = $this->buildCacheKey($requestParserData);
        return $this->redis->zscore('interceptor-cache-time', $key);
    }

    public function getOutOfDateResponses($createTimeFrom = 0, $createTimeTo = -1, $limit = 10)
    {
        $retval = [];
        $expriredCacheData = $this->redis->zrangebyscore('interceptor-cache-time',
            $createTimeFrom,
            $createTimeTo, [
                'limit' => [0, $limit],
            ]);
        foreach ($expriredCacheData as $deviceUrl) {
            $explodedDeviceUrl = explode('::', $deviceUrl);
            $retval[] = [
                'url' => $explodedDeviceUrl[2],
                'device' => $explodedDeviceUrl[1],
            ];
        }
        return $retval;
    }

    public function popOutOfDateResponses($createTimeFrom = 0, $createTimeTo = -1, $limit = 1)
    {
        $retval = [];
        $expriredCacheData = $this->redis->zrangebyscore('interceptor-cache-time',
            $createTimeFrom,
            $createTimeTo, [
                'limit' => [0, $limit],
            ]);
        foreach ($expriredCacheData as $deviceUrl) {
            $explodedDeviceUrl = explode('::', $deviceUrl);
            $retval[] = [
                'url' => $explodedDeviceUrl[2],
                'device' => $explodedDeviceUrl[1],
            ];
            $this->redis->zrem('interceptor-cache-time', $deviceUrl);
        }
        return $retval;
    }

    public function checkResponseKeys($url)
    {
        return $this->redis->keys($this->appName . '*' . $url);
    }

    public function listResponseKeys($device = '')
    {
        return $this->redis->keys($this->appName . '::' . $device . '*');
    }

    public function flush()
    {
        // flush response cache data
        $cacheKeys = $this->redis->keys($this->appName . '*');
        foreach ($cacheKeys as $cacheKey) {
            $this->redis->del($cacheKey);
        }
        // flush cache time keys
        $this->redis->del('interceptor-cache-time');
    }

    public function remove($url)
    {
        $retval = false;
        // remove response cache data
        $cacheKeys = $this->redis->keys($this->appName . '*' . $url);
        // dd($cacheKeys);
        foreach ($cacheKeys as $cacheKey) {
            $this->redis->del($cacheKey);
        }
        // remove cache time keys
        $devices = \Config::get('interceptor.devices', []);
        foreach ($devices as $device) {
            $delCount = $this->redis->zrem('interceptor-cache-time', $this->appName . '::' . $device . '::' . $url);
            if ($delCount > 0) {
                $retval = true;
            }
        }
        return $retval;
    }

    private function buildCacheKey($requestParserData)
    {
        return $this->buildCacheTags($requestParserData) . $requestParserData['url'];
    }

    private function buildCacheTags($requestParserData)
    {
        $retval = '';
        for ($i = 0; $i < count(CacheStore::CACHE_TAGS_SAMPLE); $i++) {
            $retval .= $requestParserData[CacheStore::CACHE_TAGS_SAMPLE[$i]] . '::';
        }
        return $retval;
    }
}
