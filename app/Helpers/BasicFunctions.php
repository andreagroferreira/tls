<?php

if (!function_exists('get_callback_url')) {
    function get_callback_url(String $uri): string
    {
        return getenv('PAYMENT_SERVICE_DOMAIN') . $uri;
    }
}

if (!function_exists('csvToArray')) {
    function csvToArray($filename = '', $delimiter = "\t")
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return [];
        }
        $header = null;
        $data   = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }
}
