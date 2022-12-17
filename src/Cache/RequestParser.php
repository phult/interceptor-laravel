<?php
namespace Megaads\Interceptor\Cache;

use Megaads\Interceptor\Utils\UserAgentUtil;
use \Megaads\Interceptor\Utils\URLUtil;
use Illuminate\Support\Facades\Redis;
class RequestParser
{    
    public function parse($request)
    {
        $retval = [
            'enable' => false,
        ];
        if (!\Config::get('interceptor.enable', true)) {
            return $retval;
        }
        $retval['appName'] = \Config::get('interceptor.appName', 'interceptor');
        $retval['maxAge'] = \Config::get('interceptor.maxAge', 3600);
        // method
        $retval['method'] = $request->method();
        if (strtolower($retval['method']) !== 'get') {
            return $retval;
        }
        // status
        $statuses = \Config::get('interceptor.statuses', []);
        // TODO

        // type
        $retval['type'] = $request->header('Accept');
        if (strpos(strtolower($retval['type']), 'text/html') === false
            && strpos($retval['type'], '*/*') === false) {
            return $retval;
        }
        // device
        $devices = \Config::get('interceptor.devices', []);
        $retval['device'] = UserAgentUtil::detectDevice($request->header('User-Agent'));
        if (!in_array($retval['device'], $devices)) {
            if (in_array('responsive', $devices)) {
                $retval['device'] = 'responsive';
            } else {
                return $retval;
            }
        }
        // url: n/a
        $strippedQueryParams = \Config::get('interceptor.strippedQueryParams', []);
        $retval['url'] = URLUtil::buildURL($request, $strippedQueryParams);   
        
        /** BYPASS CACHING */     
        // bypass cookies
        $retval['route'] = URLUtil::getRoute($request, $strippedQueryParams);
        $bypassedCookies = [];
        $bypassedCookies = array_merge($bypassedCookies, \Config::get('interceptor.passes.cookies', []));
        $bypassedCookies = array_merge($bypassedCookies, \Config::get('interceptor.bypasses.cookies', []));
        foreach ($bypassedCookies as $passedCookie) {
            if ($request->cookie($passedCookie) != null || isset($_COOKIE[$passedCookie])) {
                return $retval;
            }
        }
        // bypass routes
        $bypassedRoutes = [];
        $bypassedRoutes = array_merge($bypassedRoutes, \Config::get('interceptor.passes.routes', []));
        $bypassedRoutes = array_merge($bypassedRoutes, \Config::get('interceptor.bypasses.routes', []));
        if (in_array($retval['route'], $bypassedRoutes)) {
            return $retval;
        }
        // bypass IPs: 
        $clientIP = UserAgentUtil::getClientIP();
        $bypassedIps = [];
        $bypassedIps = array_merge($bypassedIps, \Config::get('interceptor.passes.ips', []));
        $bypassedIps = array_merge($bypassedIps, \Config::get('interceptor.bypasses.ips', []));
        for ($i = 0; $i < count($bypassedIps); $i++) { 
            $bypassedIpItem = $bypassedIps[$i];
            if ($clientIP === $bypassedIpItem || fnmatch($bypassedIpItem, $clientIP)) {
                return $retval;
            }
        }
        // bypass user-agents
        $userAgent = $request->header('User-Agent');
        $bypassedUserAgents = [];
        $bypassedUserAgents = array_merge($bypassedUserAgents, \Config::get('interceptor.passes.userAgents', []));
        $bypassedUserAgents = array_merge($bypassedUserAgents, \Config::get('interceptor.bypasses.userAgents', []));
        for ($i = 0; $i < count($bypassedUserAgents); $i++) { 
            $bypassedUserAgentItem = $bypassedUserAgents[$i];
            if ($userAgent === $bypassedUserAgentItem || preg_match($bypassedUserAgentItem, $userAgent)) {
                return $retval;
            }
        }

        // RETURN
        $retval['enable'] = true;
        return $retval;
    }
}
