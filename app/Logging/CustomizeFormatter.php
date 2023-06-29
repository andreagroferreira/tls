<?php

namespace App\Logging;

use Illuminate\Log\Logger;

class CustomizeFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param Logger $logger
     *
     * @return void
     */
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
        }
    }
}
