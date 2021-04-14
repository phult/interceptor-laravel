<?php
namespace Megaads\Interceptor\Cache;

use GuzzleHttp\Client;
use Megaads\Interceptor\Cache\CacheStore;
use Megaads\Interceptor\Utils\UserAgentUtil;

class CacheWorker
{
    public function __construct()
    {
        $this->cacheStore = new CacheStore();
    }

    public function refreshOutOfDateCache($limit = 1)
    {
        $retval = [];
        $refreshRate = \Config::get('interceptor.refreshRate', 86400);
        $expiredResponses = $this->cacheStore->popOutOfDateResponses(0, (time() - $refreshRate), $limit);
        foreach ($expiredResponses as $expiredResponse) {
            $retval[] = $expiredResponse['device'] . '::' . $expiredResponse['url'];
            $userAgent = UserAgentUtil::getUserAgent($expiredResponse['device']);
            $this->request($expiredResponse['url'], [
                'User-Agent' => $userAgent,
                'Referer' => 'interceptor-worker',
                'Accept' => 'text/html',
            ]);
        }
        return $retval;
    }

    public function refreshCache($url)
    {
        $devices = \Config::get('interceptor.devices', []);
        foreach ($devices as $device) {
            $userAgent = UserAgentUtil::getUserAgent($device);
            $response = $this->request($url, [
                'User-Agent' => $userAgent,
                'Referer' => 'interceptor-worker',
                'Accept' => 'text/html',
            ]);
        }
    }

    public function clearGarbageCache($maxCacheSize = null)
    {
        $retval = 0;
        if ($maxCacheSize == null) {
            $maxCacheSize = \Config::get('interceptor.maxCacheSize', 5000);
        }
        $garbageCache = $this->cacheStore->listLastActiveTimeURLs($maxCacheSize, -1);
        $retval = count($garbageCache);
        foreach ($garbageCache as $item) {
            $this->cacheStore->remove($item['url'], $item['device']);
        }
        return $retval;
    }

    private function request($url, $headers)
    {
        $client = new Client();
        $res = $client->request('GET', $url, [
            'headers' => $headers,
        ]);
        return $res->getStatusCode();
    }
}
