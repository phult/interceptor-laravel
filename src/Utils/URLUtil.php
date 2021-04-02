<?php
namespace Megaads\Interceptor\Utils;

class URLUtil
{    

    public static function buildURL($request, $strippedParams = [])
    {
        $retval = $request->url();
        $query = $request->query();
        foreach ($strippedParams as $strippedParam) {
            if (array_key_exists($strippedParam, $query)) {
                unset($query[$strippedParam]);
            }
        }
        if (count($query) > 0) {
            ksort($query);
            $retval .= '?' . http_build_query($query);
        }
        return $retval;
    }

    public static function getRoute($request) {
        return $request->path();
    }
}
