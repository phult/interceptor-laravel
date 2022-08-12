<?php
return [
    "appName" => "interceptor-app",
    "enable" => true,
    "maxCacheSize" => 5000,
    "refreshRate" => 1800,
    "maxAge" => 86400,
    "cacheConnection" => 'cache',
    "compress" => true,
    "devices" => [
        "desktop",
        "mobile",
        "tablet"
    ],
    "statuses" => [200, 203, 300, 301, 302, 304, 307, 410],
    "strippedQueryParams" => [
        "adgroupid",
        "campaignid",
        "gclid"        
    ],
    "passes" => [
        "cookies" => [
            "user_id",
        ],
        "routes" => [
            "login",
            "signup",
            "checkout/cart",
        ],
    ],
];