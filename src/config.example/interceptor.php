<?php
return [
    "appName" => "interceptor-app",
    "enable" => true,
    "refreshRate" => 1800,
    "maxAge" => 86400,
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