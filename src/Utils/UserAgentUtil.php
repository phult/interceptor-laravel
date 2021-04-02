<?php
namespace Megaads\Interceptor\Utils;

class UserAgentUtil
{
    public static function detectDevice($userAgent)
    {
        $retval = 'desktop';
        if (self::isTablet()) {
            $retval = 'table';
        } else if (self::isMobile()) {
            $retval = 'mobile';
        }
        if (self::isBot()) {
            $retval .= '_bot';
        }
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
}
