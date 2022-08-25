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
        $cacheConnection = \Config::get('interceptor.cacheConnection', '');
        $this->redis = Redis::connection($cacheConnection);
        $this->appName = \Config::get('interceptor.appName', 'interceptor');
    }
    public function saveResponseData(\Illuminate\Http\Response $response, $requestParserData)
    {
        $time = time();
        $key = $this->buildCacheKey($requestParserData);
        // if (!\Cache::tags($tags)->has($key)) {
        $this->redis->set($key, $this->compress($response->getContent()));
        $this->redis->zadd('interceptor-cache-time', $time, $key);
        // }
        return $time;
    }

    public function saveLastActiveTimeURL($requestParserData)
    {
        $time = time();
        $key = $this->buildCacheKey($requestParserData);
        $this->redis->zadd('interceptor-last-active-time', $time, $key);
        return $time;
    }

    public function listLastActiveTimeURLs($startIdx = 0, $stopIdx = -1)
    {
        $retval = [];
        $cacheKeys = $this->redis->zrevrange('interceptor-last-active-time', $startIdx, $stopIdx);
        foreach ($cacheKeys as $cacheKey) {
            $retval[] = $this->parseCacheKey($cacheKey);
        }
        return $retval;
    }

    public function getResponseData($requestParserData)
    {
        $key = $this->buildCacheKey($requestParserData);
        $retval = $this->redis->get($key);
        if ($retval != null) {
            return  $this->decompress($retval);
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
        foreach ($expriredCacheData as $item) {
            $retval[] = $this->parseCacheKey($item);
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
        foreach ($expriredCacheData as $item) {
            $retval[] = $this->parseCacheKey($item);
            $this->redis->zrem('interceptor-cache-time', $item);
        }
        return $retval;
    }

    public function isOutOfDateResponse($requestParserData)
    {
        $retval = false;
        $refreshRate = \Config::get('interceptor.refreshRate', 86400);
        $cacheTime = $this->getResponseCacheTime($requestParserData);
        if ($cacheTime == null || $cacheTime < (time() - $refreshRate)) {
            $retval = true;
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
        // flush last active time keys
        $this->redis->del('interceptor-last-active-time');
    }

    public function remove($url, $device = null)
    {
        $retval = false;
        // remove response cache data
        $keyPattern = $device != null ? ($this->appName . '::' . $device . '::' . $url) : ($this->appName . '*' . $url);
        $cacheKeys = $this->redis->keys($keyPattern);
        foreach ($cacheKeys as $cacheKey) {
            $this->redis->del($cacheKey);
        }
        // remove cache time keys and last active time keys
        if ($device != null) {
            $delCount = $this->redis->zrem('interceptor-cache-time', $this->appName . '::' . $device . '::' . $url);
            $this->redis->zrem('interceptor-last-active-time', $this->appName . '::' . $device . '::' . $url);
            if ($delCount > 0) {
                $retval = true;
            }
        } else {
            $devices = \Config::get('interceptor.devices', []);
            foreach ($devices as $device) {
                $delCount = $this->redis->zrem('interceptor-cache-time', $this->appName . '::' . $device . '::' . $url);
                $this->redis->zrem('interceptor-last-active-time', $this->appName . '::' . $device . '::' . $url);
                if ($delCount > 0) {
                    $retval = true;
                }
            }
        }
        return $retval;
    }

    public function summary($type = 'hit')
    {
        if (\Config::get('interceptor.summary', false)) {
            // get the current hour timestamp in seconds
            $time = mktime(date("H") , 0, 0,  date("m"), date("d"), date("Y"));
            // save to a sorted-set
            return $this->redis->zincrby($this->appName . '.interceptor-summary.' . $type, 1, $time);        
        }
        return false;
    }

    private function parseCacheKey($cacheKey)
    {
        $explodedCacheKey = explode('::', $cacheKey);
        return [
            'url' => $explodedCacheKey[2],
            'device' => $explodedCacheKey[1],
            'appName' => $explodedCacheKey[0],
        ];
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

    private function compress($data) {
        if (\Config::get('interceptor.compress', true)) {
            return gzcompress($data, 9);
        }
        return $data;
    }

    private function decompress($data) {
        if (\Config::get('interceptor.compress', true)) {
            return gzuncompress($data);
        }
        return $data;
    }
}
