<?php

function DeviceType() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $patterns = [
        '/Android/i',
        '/webOS/i',
        '/iPhone/i',
        '/iPad/i',
        '/iPod/i',
        '/BlackBerry/i',
        '/Windows Phone/i',
        '/Opera Mini/i',
        '/IEMobile/i',
        '/Mobile/i'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return 'mobile';
        }
    }
    return 'desktop';
}
