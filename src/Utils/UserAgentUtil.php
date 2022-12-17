<?php
namespace Megaads\Interceptor\Utils;

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
        if (self::isTablet()) {
            $retval = 'tablet';
        } else if (self::isMobile()) {
            $retval = 'mobile';
        }
        // if (self::isBot()) {
        //     $retval .= '_bot';
        // }
        return $retval;
    }
    
    public static function isMobile()
    {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "");
    }

    public static function isTablet()
    {
        return preg_match("/(ipad|tablet)/i", isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "");
    }

    public static function isBot()
    {
        return preg_match("/rambler|abacho|acoi|accona|aspseek|altavista|estyle|scrubby|lycos|geona|ia_archiver|alexa|sogou|skype|facebook|twitter|pinterest|linkedin|naver|bing|google|yahoo|duckduckgo|yandex|baidu|teoma|xing|java\/1.7.0_45|bot|crawl|slurp|spider|mediapartners|\sask\s|\saol\s/i", isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "");
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
