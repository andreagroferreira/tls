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
    $data = [];
    $content = explode(PHP_EOL, $content);
    foreach ($content as $k => $v) {
        if ($v) {
            $content[$k] = str_getcsv($v, $delimiter);
        }
    }
    $content = array_filter($content);
    foreach ($content as $k => $v) {
        if ($k != 0) {
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

function getCountryName($countryCode)
{
    return config('list_countries.'. $countryCode)['gc_name'];
}

function getCityName($cityCode)
{
    return config('list_city.'. $cityCode)['gcc_name'];
}

function authcode($string, $key = '', $operation = false, $expiry = 0){
    $ckey_length = 4;
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation? substr($string, 0, $ckey_length):substr(md5(microtime()), -$ckey_length)) : '';
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    $string = $operation? base64_decode(substr($string, $ckey_length)) :
        sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation) {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
            substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}
