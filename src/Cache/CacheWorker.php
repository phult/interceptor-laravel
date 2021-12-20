<?php
namespace Megaads\Interceptor\Cache;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;
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

    public function refreshCache($url, $devices = [], $async = false)
    {
        if ($devices == null || count($devices) == 0) {
            $devices = \Config::get('interceptor.devices', []);
        }
        foreach ($devices as $device) {
            $userAgent = UserAgentUtil::getUserAgent($device);
            if ($async) {
                $this->requestAsync($url, [
                    'User-Agent: ' . $userAgent,
                    'Referer: interceptor-worker',
                    'Accept: text/html',
                ]);
            } else {
                $this->request($url, [
                    'User-Agent' => $userAgent,
                    'Referer' => 'interceptor-worker',
                    'Accept' => 'text/html',
                ]);
            }
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
        $retval = null;
        try {
            $client = new Client();
            $res = $client->request('GET', $url, [
                'headers' => $headers,
                'http_errors' => false,
            ]);
            $retval = $res->getStatusCode();
        } catch (Exception $ex) {
            $retval = 500;
        }
        return $retval;
    }
    protected function requestAsync($url, $headers)
    {
        $channel = curl_init();
        curl_setopt($channel, CURLOPT_URL, $url);
        curl_setopt($channel, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($channel, CURLOPT_NOSIGNAL, 1);
        curl_setopt($channel, CURLOPT_TIMEOUT_MS, 50);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($channel);
        curl_close($channel);
    }
}
