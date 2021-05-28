<?php

namespace App\Logging;

class PureMessageLogFormatter

{

    /**

     * keep log content only, remove useless timestamp and log type
     * @param  \Illuminate\Log\Logger  $logger

     * @return void

     */

    public function __invoke($logger)

    {

        foreach ($logger->getHandlers() as $handler) {

            $json_formatter = new \Monolog\Formatter\LineFormatter("%message%\n");
            $handler->setFormatter($json_formatter);

        }

    }

}
