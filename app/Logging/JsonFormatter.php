<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

class JsonFormatter extends BaseJsonFormatter
{
    protected $includeStacktraces = true;
    protected $appendNewline  = true;

    public function format(array $record): string
    {
        if (!empty($record)) $record['uuid'] = request()->get('log-uuid');
        $json = $this->toJson($this->normalize($record), true) . ($this->appendNewline ? "\n" : '');
        return $json;
    }
}
