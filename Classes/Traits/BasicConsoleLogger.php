<?php
namespace MCStreetguy\FusionLinter\Traits;

use Neos\Flow\Annotations as Flow;

/**
 *
 */
trait BasicConsoleLogger
{
    /**
     * Log a message to the terminal.
     *
     * @param string $message
     * @return void
     */
    protected function log(string $message)
    {
        if (FLOW_SAPITYPE === 'CLI') {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Print a warning to the terminal.
     *
     * @param string $message
     * @return void
     */
    protected function warning(string $message)
    {
        if (FLOW_SAPITYPE === 'CLI') {
            echo "\033[33m" . $message . "\033[0m" . PHP_EOL;
        }
    }

    /**
     * Print an error to the terminal.
     *
     * @param string $message
     * @return void
     */
    protected function error(string $message)
    {
        if (FLOW_SAPITYPE === 'CLI') {
            echo "\033[31m\033[1m" . $message . "\033[0m" . PHP_EOL;
        }
    }

    /**
     * Print a success message to the terminal.
     *
     * @param string $message
     * @return void
     */
    protected function success(string $message)
    {
        if (FLOW_SAPITYPE === 'CLI') {
            echo "\033[32m" . $message . "\033[0m" . PHP_EOL;
        } 
    }

    /**
     * Print an info message to the terminal.
     *
     * @param string $message
     * @return void
     */
    protected function info(string $message)
    {
        if (FLOW_SAPITYPE === 'CLI') {
            echo "\033[0;36m" . $message . "\033[0m" . PHP_EOL;
        }
    }
}
