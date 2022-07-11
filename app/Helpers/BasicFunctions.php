<?php

function get_callback_url(string $uri): string
{
    return getenv('PAYMENT_SERVICE_DOMAIN') . $uri;
}

function csv_to_array($filename = '', $delimiter = "\t")
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

function csv_content_array($content = '')
{
    if (empty($content)) {
        return [];
    }
    $data   = [];
    $content = explode(PHP_EOL,$content);
    foreach ($content as $k=>$v){
        $content[$k] = str_getcsv($v);
    }
    foreach ($content as $k=>$v){
        if($k != 0){
            $data[] = array_combine($content[0], $content[$k]);
        }
    }
    return $data;
}

function get_csv_content($filename = '')
{
    $content = '';
    if (!file_exists($filename) || !is_readable($filename)) {
        return $content;
    }
    $handle = fopen($filename,"r");
    while(! feof($handle))
    {
        $content .=fgets($handle);
    }
    fclose($handle);
    return $content;
}

function get_file_size($byte)
{
    $KB = 1024;
    $MB = 1024 * $KB;
    $GB = 1024 * $MB;
    $TB = 1024 * $GB;
    if ($byte < $KB) {
        return $byte . "B";
    } elseif ($byte < $MB) {
        return round($byte / $KB, 2) . "K";
    } elseif ($byte < $GB) {
        return round($byte / $MB, 2) . "M";
    } elseif ($byte < $TB) {
        return round($byte / $GB, 2) . "G";
    } else {
        return round($byte / $TB, 2) . "T";
    }
}

function in_list($needle, $haystack): bool
{
    $rule_array = explode(',',  preg_replace('/^in_list\((.*)?\)/', '$1', $haystack));
    if(is_string($needle)) {
        return in_array($needle, $rule_array);
    } else if (is_array($needle)) {
        return !empty(array_intersect($needle, $rule_array));
    } else {
        return false;
    }
}

function not_in_list($needle, $haystack): bool
{
    $rule_array = explode(',',  preg_replace('/^not_in_list\((.*)?\)/', '$1', $haystack));
    if(is_string($needle)) {
        return !in_array($needle, $rule_array);
    } else if (is_array($needle)) {
        return empty(array_intersect($needle, $rule_array));
    } else {
        return false;
    }
}

function workflow_status($stages_status, $haystack):bool
{
    $preg_str = preg_replace('/^workflow_status\((.*)?\)/', '$1', $haystack);
    $rules = explode(',', str_replace(['\'', ' ', '"'], '', $preg_str));
    $stage = array_shift($rules);
    if(empty($stages_status[$stage])) {
        return false;
    } else {
        return in_array($stages_status[$stage], $rules);
    }
}
