<?php

function get_callback_url(string $uri): string
{
    return getenv('PAYMENT_SERVICE_DOMAIN') . $uri;
}

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
