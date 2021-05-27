<?php

if (!function_exists('get_callback_url')) {
    function get_callback_url(String $uri): string
    {
        return getenv('PAYMENT_SERVICE_DOMAIN') . $uri;
    }
}
