<?php
namespace Megaads\Interceptor\Utils;

use Megaads\Interceptor\Utils\MobileDetect;

class UserAgentUtil
{
    public static function getUserAgent($device) {
        $retval = null;
        switch ($device) {
            case "desktop": {
                $retval = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36";
                break;
            }
            case "mobile": {
                $retval = "Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1";
                break;
            }
            case "tablet": {
                $retval = "Mozilla/5.0 (iPad; CPU OS 11_0 like Mac OS X) AppleWebKit/604.1.34 (KHTML, like Gecko) Version/11.0 Mobile/15A5341f Safari/604.1";
                break;
            }
            default: // desktop
                $retval = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36";
                break;
        }
        return $retval;
    }

    public static function detectDevice($userAgent)
    {
        $retval = 'desktop';
        $detect = new MobileDetect();
        try {
            if ($detect->isTablet() || $detect->isTablet($userAgent)) {
                $retval = 'tablet';
            } else if ($detect->isMobile() || $detect->isMobile($userAgent)) {
                $retval = 'mobile';
            }
        } catch (\Exception $e) {
            $retval = 'na';
        }
        // if (self::isBot()) {
        //     $retval .= '_bot';
        // }
        return $retval;
    }
    
    public static function getClientIP()
    {
        $retVal = 'UNKNOWN';
        if (key_exists("HTTP_CLIENT_IP", $_SERVER))
            $retVal = $_SERVER['HTTP_CLIENT_IP'];
        else if (key_exists("HTTP_X_FORWARDED_FOR", $_SERVER))
            $retVal = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (key_exists("HTTP_X_FORWARDED", $_SERVER))
            $retVal = $_SERVER['HTTP_X_FORWARDED'];
        else if (key_exists("HTTP_FORWARDED_FOR", $_SERVER))
            $retVal = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (key_exists("HTTP_FORWARDED", $_SERVER))
            $retVal = $_SERVER['HTTP_FORWARDED'];
        else if (key_exists("REMOTE_ADDR", $_SERVER))
            $retVal = $_SERVER['REMOTE_ADDR'];
        return $retVal;
    }
}
