<?php
namespace Megaads\Interceptor\Cache;

use Illuminate\Support\Facades\Redis;

class CacheStore
{
    const CACHE_TAGS_SAMPLE = ['appName', 'device'];
    protected $redis;
    protected $appName;
    protected $isRedisConnected = false;
    protected $saveToFile = false;
    public function __construct()
    {
        $cacheConnection = \Config::get('interceptor.cacheConnection', 'default');
        $this->redis = Redis::connection($cacheConnection);
        try {
            $this->redis->ping();
            $this->isRedisConnected = true;
        } catch (\Throwable $th) {
            $this->isRedisConnected = false;
        }
        $this->appName = \Config::get('interceptor.appName', 'interceptor');
        $this->saveToFile = \Config::get('interceptor.saveToFile', false);
    }
    public function saveResponseData(\Illuminate\Http\Response $response, $requestParserData)
    {
        $time = time();
        if (!$this->isRedisConnected) {
            return $time;
        }
        $key = $this->buildCacheKey($requestParserData);
        // if (!\Cache::tags($tags)->has($key)) {
        $this->saveContentCache($key, $this->compress($response->getContent()));
        $this->redis->zadd($this->buildCacheKey('interceptor-cache-time'), $time, $key);
        // }
        //auto execute the garage collector
        $cacheSize = \Config::get('interceptor.maxCacheSize', 5000);
        $autoCollectGarbageCacheSize = \Config::get('interceptor.autoCollectGarbageCacheSize', $cacheSize + 1000);
        $this->clearGarbageCache($cacheSize, $autoCollectGarbageCacheSize - $cacheSize);
        // return cache time
        return $time;
    }

    public function saveLastActiveTimeURL($requestParserData)
    {
        $time = time();
        if (!$this->isRedisConnected) {
            return $time;
        }
        $key = $this->buildCacheKey($requestParserData);
        $this->redis->zadd($this->buildCacheKey('interceptor-last-active-time'), $time, $key);
        return $time;
    }

    public function listLastActiveTimeURLs($startIdx = 0, $stopIdx = -1)
    {
        $retval = [];
        if (!$this->isRedisConnected) {
            return $retval;
        }
        $cacheKeys = $this->redis->zrevrange($this->buildCacheKey('interceptor-last-active-time'), $startIdx, $stopIdx);
        foreach ($cacheKeys as $cacheKey) {
            $retval[] = $this->parseCacheKey($cacheKey);
        }
        return $retval;
    }

    public function cacheLength()
    {
        return $this->redis->zcount($this->buildCacheKey('interceptor-last-active-time'), '-inf', '+inf');
    }

    public function getResponseData($requestParserData)
    {
        $retval = null;
        if ($this->isRedisConnected) {
            $key = $this->buildCacheKey($requestParserData);
            $content = $this->readContentCache($key);
            if ($content != null && $content != '') {
                $content = $this->decompress($content);
                if ($content != null && $content !== false && $content !== '') {
                    $retval = $content;
                }
            }
        }
        return $retval;
    }

    public function getResponseCacheTime($requestParserData)
    {
        $retval = time();
        if (!$this->isRedisConnected) {
            return $retval;
        }
        $key = $this->buildCacheKey($requestParserData);
        $retval = $this->redis->zscore($this->buildCacheKey('interceptor-cache-time'), $key);
        return $retval;
    }

    public function getOutOfDateResponses($createTimeFrom = 0, $createTimeTo = -1, $limit = 10)
    {
        $retval = [];
        if (!$this->isRedisConnected) {
            return $retval;
        }
        $expriredCacheData = $this->redis->zrangebyscore($this->buildCacheKey('interceptor-cache-time'),
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
        if (!$this->isRedisConnected) {
            return $retval;
        }
        $expriredCacheData = $this->redis->zrangebyscore($this->buildCacheKey('interceptor-cache-time'),
            $createTimeFrom,
            $createTimeTo, [
                'limit' => [0, $limit],
            ]);
        foreach ($expriredCacheData as $item) {
            $retval[] = $this->parseCacheKey($item);
            $this->redis->zrem($this->buildCacheKey('interceptor-cache-time'), $item);
        }
        return $retval;
    }

    public function isOutOfDateResponse($requestParserData)
    {
        $retval = false;
        if (!$this->isRedisConnected) {
            return $retval;
        }
        $refreshRate = \Config::get('interceptor.refreshRate', 86400);
        $cacheTime = $this->getResponseCacheTime($requestParserData);
        if ($cacheTime == null || $cacheTime < (time() - $refreshRate)) {
            $retval = true;
        }
        return $retval;
    }

    public function isReachedMaxAgeResponse($requestParserData)
    {
        $retval = false;
        if (!$this->isRedisConnected) {
            return $retval;
        }
        $maxAge = \Config::get('interceptor.maxAge', 86400);
        $cacheTime = $this->getResponseCacheTime($requestParserData);
        if ($cacheTime == null || $cacheTime < (time() - $maxAge)) {
            $retval = true;
        }
        return $retval;
    }

    public function checkResponseKeys($url)
    {
        if (!$this->isRedisConnected) {
            return [];
        }
        return $this->redis->keys($this->appName . '*' . $url);
    }

    public function listResponseKeys($device = '')
    {
        if (!$this->isRedisConnected) {
            return [];
        }
        return $this->redis->keys($this->appName . '::' . $device . '*');
    }

    public function flush()
    {
        $retval = false;
        if (!$this->isRedisConnected) {
            return $retval;
        }
        // flush response cache data
        $this->removeAllContentCache();
        // flush cache time keys
        $this->redis->del($this->buildCacheKey('interceptor-cache-time'));
        // flush last active time keys
        $this->redis->del($this->buildCacheKey('interceptor-last-active-time'));
        $retval = true;
        return $retval;
    }

    public function remove($url, $device = null)
    {
        $retval = false;
        if (!$this->isRedisConnected) {
            return $retval;
        }
        // remove response cache data
        $this->removeContentCache($url, $device);

        // remove cache time keys and last active time keys
        if ($device != null) {
            $delCount = $this->redis->zrem($this->buildCacheKey('interceptor-cache-time'), $this->appName . '::' . $device . '::' . $url);
            $this->redis->zrem($this->buildCacheKey('interceptor-last-active-time'), $this->appName . '::' . $device . '::' . $url);
            if ($delCount > 0) {
                $retval = true;
            }
        } else {
            $devices = \Config::get('interceptor.devices', []);
            foreach ($devices as $device) {
                $delCount = $this->redis->zrem($this->buildCacheKey('interceptor-cache-time'), $this->appName . '::' . $device . '::' . $url);
                $this->redis->zrem($this->buildCacheKey('interceptor-last-active-time'), $this->appName . '::' . $device . '::' . $url);
                if ($delCount > 0) {
                    $retval = true;
                }
            }
        }
        return $retval;
    }

    /**
     * Cache garbage collector
     * @param $maxCacheSize Max number of cache items
     * @param $extraCacheSize Extra number of cache items to execute the garbage collector
     * @return Number of removed cache-items
     */
    public function clearGarbageCache($maxCacheSize = null, $extraCacheSize = 0)
    {
        $retval = 0;
        if (!$this->isRedisConnected) {
            return $retval;
        }
        if ($maxCacheSize == null) {
            $maxCacheSize = \Config::get('interceptor.maxCacheSize', 5000);
        }
        $cacheLength = $this->cacheLength();
        if ($cacheLength > $maxCacheSize + $extraCacheSize) {
            $garbageCache = $this->listLastActiveTimeURLs($maxCacheSize, -1);
            $retval = count($garbageCache);
            foreach ($garbageCache as $item) {
                $this->remove($item['url'], $item['device']);
            }
        }
        return $retval;
    }

    public function summary($type = 'hit')
    {
        $retval = false;
        if (!$this->isRedisConnected) {
            return $retval;
        }
        if (\Config::get('interceptor.summary', false)) {
            // get the current hour timestamp in seconds
            $time = mktime(date("H"), 0, 0, date("m"), date("d"), date("Y"));
            // save to a sorted-set
            $retval = $this->redis->zincrby($this->appName . '.interceptor-summary.' . $type, 1, $time);
        }
        return $retval;
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

    private function buildCacheKey($inputData)
    {
        if (is_array($inputData)) {
            return $this->buildCacheTags($inputData) . $inputData['url'];
        } else {
            return $this->appName . '::' . $inputData;
        }
    }

    private function buildCacheTags($requestParserData)
    {
        $retval = '';
        for ($i = 0; $i < count(CacheStore::CACHE_TAGS_SAMPLE); $i++) {
            $retval .= $requestParserData[CacheStore::CACHE_TAGS_SAMPLE[$i]] . '::';
        }
        return $retval;
    }

    private function compress($data)
    {
        if (\Config::get('interceptor.compress', true)) {
            return gzcompress($data, \Config::get('interceptor.compressLevel', 9));
        }
        return $data;
    }

    private function decompress($data)
    {
        if (\Config::get('interceptor.compress', true)) {
            try {
                return gzuncompress($data);
            } catch (\Throwable $th) {
                return "";
            }
        }
        return $data;
    }

    function saveContentCache($key, $content)
    {
        if ($this->saveToFile === false) {
            $this->redis->set($key, $content);
        } else {
            $directory = storage_path('cache/interceptor/' . $this->appName);
            $filename = md5($key);
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            $filePath = $directory . '/' . $filename;
            file_put_contents($filePath, $content);
        }
    }
    function readContentCache($key)
    {
        $retval = null;
        if ($this->saveToFile === false) {
            $retval = $this->redis->get($key);
        } else {
            $directory = storage_path('cache/interceptor/' . $this->appName);
            $filename = md5($key);
            $filePath = $directory . '/' . $filename;
            if (file_exists($filePath)) {
                $retval = file_get_contents($filePath);
            }
        }
        return $retval;
    }
    function removeContentCache($url, $device = null)
    {
        if ($this->saveToFile === false) {
            $keyPattern = $device != null ? ($this->appName . '::' . $device . '::' . $url) : ($this->appName . '*' . $url);
            $cacheKeys = $this->redis->keys($keyPattern);
            foreach ($cacheKeys as $cacheKey) {
                $this->redis->del($cacheKey);
            }
        } else {
            if ($device != null) {
                $key = $this->appName . '::' . $device . '::' . $url;
                $directory = storage_path('cache/interceptor/' . $this->appName);
                $filename = md5($key);
                $filePath = $directory . '/' . $filename;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            } else {
                $devices = \Config::get('interceptor.devices', []);
                foreach ($devices as $device) {
                    $key = $this->appName . '::' . $device . '::' . $url;
                    $directory = storage_path('cache/interceptor/' . $this->appName);
                    $filename = md5($key);
                    $filePath = $directory . '/' . $filename;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }
    }
    function removeAllContentCache()
    {
        if ($this->saveToFile === false) {
            $cacheKeys = $this->redis->keys($this->appName . '*');
            foreach ($cacheKeys as $cacheKey) {
                $this->redis->del($cacheKey);
            }
        } else {
            $directory = storage_path('cache/interceptor/' . $this->appName);
            if (file_exists($directory)) {
                $files = glob($directory . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }
    }

}
