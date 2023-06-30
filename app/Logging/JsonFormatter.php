<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

class JsonFormatter extends BaseJsonFormatter
{
    protected bool $includeStacktraces = true;
    protected bool $appendNewline = true;
}
