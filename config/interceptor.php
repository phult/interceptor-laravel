<?php
return [
    "appName" => "interceptor-app",
    "enable" => true,
    "maxCacheSize" => 5000,
    "autoCollectGarbageCacheSize" => 6000, // Cache size to excecute the garbage collector automatically
    "refreshRate" => 1800,
    "maxAge" => 86400,
    "cacheConnection" => 'cache',
    "compress" => true, // Require to flush all cache-data after changing this configuration
    "summary" => false,
    "devices" => [
        "desktop",
        "mobile",
        "tablet",
    ],
    "statuses" => [200, 203, 300, 301, 302, 304, 307, 410],
    "strippedQueryParams" => [
        "utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "adgroupid", "campaignid", "gclid", "button_tr", "affiliate_id", "slider", "zarsrc", "fbclid", "email", "url", "popup", "ads", "custom_id", "gbraid", "wbraid",
    ],
    "bypasses" => [
        "cookies" => [
            "user_id",
        ],
        "ips" => [
            /** Wildcard is supported **/
            "127.0.0.1",
            "127.0.0.*",
        ],
        "userAgents" => [
            /** Regex is supported **/
            "crawler",
            "/(.*)botnet(.*)/", 
        ],
        "routes" => [
            "login",
            "signup",
            "checkout/cart",
        ],
    ],
];
