<?php

use Illuminate\Support\Facades\Cache;

function get_callback_url(string $uri): string
{
    return getenv('PAYMENT_SERVICE_DOMAIN') . $uri;
}

function baseCheck($permission, $groupRole)
{
    $resource = [];
    if (!$permission) {
        return false;
    }
    foreach ($permission as $item) {
        $resource[] = '/TLSagent/ww/' . $item;
    }
    $response = array_intersect($resource, $groupRole);
    if (!$response) {
        return false;
    }

    return true;
}

function getPublicKey()
{
    $cache_key = 'keycloak_public_key';

    return Cache::remember($cache_key, 60 * 60 * 24, function () {
        $keycloakRealm = app()->make(App\Services\ApiService::class)->callKeycloakApi('get', 'realms/atlas-private-azure');
        if ($keycloakRealm['status'] == 200) {
            $public_key_string = wordwrap($keycloakRealm['body']['public_key'], 65, "\n", true);

            return <<<EOD
                -----BEGIN PUBLIC KEY-----
                {$public_key_string}
                -----END PUBLIC KEY-----
                EOD;
        }
    });
}

function csv_to_array($filename = '', $delimiter = "\t")
{
    if (!file_exists($filename) || !is_readable($filename)) {
        return [];
    }
    $header = null;
    $data = [];
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

function csv_content_array($content = '', $delimiter = ',')
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
        return $byte . 'B';
    }
    if ($byte < $MB) {
        return round($byte / $KB, 2) . 'K';
    }
    if ($byte < $GB) {
        return round($byte / $MB, 2) . 'M';
    }
    if ($byte < $TB) {
        return round($byte / $GB, 2) . 'G';
    }

    return round($byte / $TB, 2) . 'T';
}

function in_list($needle, $haystack): bool
{
    $rule_array = explode(',', preg_replace('/^in_list\((.*)?\)/', '$1', $haystack));
    if (is_string($needle)) {
        return in_array($needle, $rule_array);
    }
    if (is_array($needle)) {
        return !empty(array_intersect($needle, $rule_array));
    }

    return false;
}

function not_in_list($needle, $haystack): bool
{
    $rule_array = explode(',', preg_replace('/^not_in_list\((.*)?\)/', '$1', $haystack));
    if (is_string($needle)) {
        return !in_array($needle, $rule_array);
    }
    if (is_array($needle)) {
        return empty(array_intersect($needle, $rule_array));
    }

    return false;
}

function workflow_status($stages_status, $haystack): bool
{
    $preg_str = preg_replace('/^workflow_status\((.*)?\)/', '$1', $haystack);
    $rules = explode(',', str_replace(['\'', ' ', '"'], '', $preg_str));
    $stage = array_shift($rules);
    if (empty($stages_status[$stage])) {
        return false;
    }

    return in_array($stages_status[$stage], $rules);
}

function csv2array($in, $option = '', $separator = "\t", $include_comment = false, $regex = '')
{
    //  $in = ereg_replace("\"([^\t]*)\"","\\1",$in); // TO BE TESTED, was trying to remove the quotes in fields
    $arr = [];
    if (!is_array($in)) {
        $in = explode("\r", $in);
    }
    $first_line = array_shift($in);
    $field_names = explode($separator, $first_line);
    $id_field = array_shift($field_names);
    foreach ($in as $line_num => $line) {
        if (trim($line) == '') {
            continue;
        }
        if ($include_comment == false) {
            if (strpos($line, '#') === 0) {
                continue;
            } // comments
        }
        if ($regex != '') {
            if (preg_match($regex, trim($line)) === 0) {
                continue;
            }
        }
        $values = explode($separator, $line);
        $id = array_shift($values);
        $id = trim($id);
        if (isset($arr[$id]) and ($option != 'MULTIPLE_ENTRIES') and ($option != 'INDEXED_ARRAY')) {
            $err_msg = "csv2array() found duplicate line for id '{$id}' (around line " . ($line_num + 2) . ')';
            // error() function might not be declared at this time
            if (function_exists('error')) {
                error($err_msg);
            } else {
                error_log($err_msg);
            }

            return false;
            // terminateTech();
        }
        $new_row = [];
        if ($option == 'INDEXED_ARRAY') {
            $new_row[trim($id_field)] = $id;
        }
        foreach ($field_names as $k => $field_name) {
            $field_name = trim($field_name);
            $new_row[$field_name] = trim(@$values[$k]);
        }
        if ($option == 'MULTIPLE_ENTRIES') { // each id (first col) may have more than one line
            $arr[$id][] = $new_row;
        } elseif ($option == 'INDEXED_ARRAY') {
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
    return config('list_countries.' . $countryCode)['gc_name'] ?? 'N/A';
}

function getCityName($cityCode)
{
    return config('list_city.' . $cityCode)['gcc_name'] ?? 'N/A';
}

/**
 * 将字符串参数变为数组
 * id=123&type=abc.
 *
 * @param $query
 *
 * @return array
 */
function convertUrlQuery($query): array
{
    $queryParts = explode('&', $query);
    $params = [];
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }

    return $params;
}

/**
 * @param array  $transaction
 * @param string $storageService
 *
 * @return string
 */
function getFilePath(array $transaction, string $storageService = 'file-library'): string
{
    if ($storageService === 's3') {
        return array_get($transaction, 't_client') . '/' . array_get($transaction, 't_xref_fg_id') . '/' . array_get($transaction, 't_transaction_id') . '.pdf';
    }

    if ($storageService === 'minio') {
        return array_get($transaction, 't_client') . '/' . array_get($transaction, 't_xref_fg_id') . '/' . array_get($transaction, 't_transaction_id');
    }

    $country = substr($transaction['t_issuer'], 0, 2);
    $city = substr($transaction['t_issuer'], 2, 3);

    return 'invoice/WW/' . $country . '/' . $city . '/' . array_get($transaction, 't_xref_fg_id') . '/' . array_get($transaction, 't_transaction_id') . '.pdf';
}

function getProjectId()
{
    $project = getenv('CLIENT');

    switch ($project) {
        case 'gss-us':
            $projectCode = 'us';

            break;

        case 'srf-fr':
            $projectCode = 'srf_fr';

            break;

        case 'hmpo-uk':
            $projectCode = 'hmpo_uk';

            break;

        case 'leg-be':
            $projectCode = 'leg_be';

            break;

        case 'leg-de':
            $projectCode = 'leg_de';

            break;

        case 'biolab-ma':
            $projectCode = 'biolab_ma';

            break;

        default:
            $projectCode = substr($project, -2);
    }

    return $projectCode;
}

/**
 * getLocalDateTimeFromUTC.
 *
 * @param string $DBdate
 * @param string $offset
 *
 * @return string
 */
function getLocalDateTimeFromUTC(string $DBdate, string $timezoneOffset): string
{
    date_default_timezone_set('UTC');
    $date = strtotime($DBdate);
    [$hours, $minutes] = explode(':', $timezoneOffset);
    $seconds = $hours * 60 * 60 + $minutes * 60;
    $tz = timezone_name_from_abbr('', $seconds, 1);
    if ($tz === false) {
        $tz = timezone_name_from_abbr('', $seconds, 0);
    }
    date_default_timezone_set($tz);

    return date('Y-m-d h:i:s A', $date);
}
