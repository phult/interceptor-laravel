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
            return $retval;
        }
        // url: n/a
        $strippedQueryParams = \Config::get('interceptor.strippedQueryParams', []);
        $retval['url'] = URLUtil::buildURL($request, $strippedQueryParams);
        // passes: cookies, routes
        $retval['route'] = URLUtil::getRoute($request, $strippedQueryParams);
        $passedCookies = \Config::get('interceptor.passes.cookies', []);
        foreach ($passedCookies as $passedCookie) {
            if ($request->cookie($passedCookie) != null || isset($_COOKIE[$passedCookie])) {
                return $retval;
            }
        }
        $passedRoutes = \Config::get('interceptor.passes.routes', []);
        if (in_array($retval['route'], $passedRoutes)) {
            return $retval;
        }
        $retval['enable'] = true;
        return $retval;
    }
}
