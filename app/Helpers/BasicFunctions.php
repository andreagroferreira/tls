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

function csv_content_array($content = '', $delimiter = ",")
{
    if (empty($content)) {
        return [];
    }
    $data   = [];
    $content = explode(PHP_EOL,$content);
    foreach ($content as $k=>$v){
        if($v){
            $content[$k] = str_getcsv($v,$delimiter);
        }
    }
    $content = array_filter($content);
    foreach ($content as $k=>$v){
        if($k != 0){
            $data[] = array_combine($content[0], $content[$k]);
        }
    }
    return $data;
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

function csv2array($in, $option = '', $separator = "\t", $include_comment = false, $regex = '')
{
    //  $in = ereg_replace("\"([^\t]*)\"","\\1",$in); // TO BE TESTED, was trying to remove the quotes in fields
    $arr = array();
    if (!is_array($in)) {
        $in = explode("\r", $in);
    }
    $first_line = array_shift($in);
    $field_names = explode($separator, $first_line);
    $id_field = array_shift($field_names);
    foreach ($in as $line_num => $line) {
        if (trim($line) == '') continue;
        if ($include_comment == false) {
            if (strpos($line, '#') === 0) continue; // comments
        }
        if ($regex != '') {
            if (preg_match($regex, trim($line)) === 0) continue;
        }
        $values = explode($separator, $line);
        $id = array_shift($values);
        $id = trim($id);
        if (isset($arr[$id]) and ($option != 'MULTIPLE_ENTRIES') and ($option != 'INDEXED_ARRAY')) {
            $err_msg = "csv2array() found duplicate line for id '$id' (around line " . ($line_num + 2) . ")";
            // error() function might not be declared at this time
            if (function_exists('error')) {
                error($err_msg);
            } else {
                error_log($err_msg);
            }
            return false;
            //terminateTech();
        }
        $new_row = array();
        if ($option == 'INDEXED_ARRAY') {
            $new_row[trim($id_field)] = $id;
        }
        foreach ($field_names as $k => $field_name) {
            $field_name = trim($field_name);
            $new_row[$field_name] = trim(@$values[$k]);
        }
        if ($option == 'MULTIPLE_ENTRIES') { // each id (first col) may have more than one line
            $arr[$id][] = $new_row;
        } else if ($option == 'INDEXED_ARRAY') {
            $arr[] = $new_row;
        } else {
            $arr[$id] = $new_row;
        }
    }
    //  echo "<pre>"; print_r($arr); echo "</pre>";
    return $arr;
}
