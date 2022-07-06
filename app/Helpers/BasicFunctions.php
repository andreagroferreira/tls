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
