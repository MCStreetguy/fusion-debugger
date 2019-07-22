<?php
namespace MCStreetguy\FusionLinter\Service;

use Neos\Flow\Cli\ConsoleOutput;

class IO
{
    /**
     * @var ConsoleOutput|null
     */
    protected static $output;

    public static function injectConsoleOutput(ConsoleOutput $output)
    {
        self::$output = $output;
    }

    /**
     * Log a message to the terminal.
     *
     * @param string $message
     * @return void
     */
    public static function log(string $message)
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
    public static function warning(string $message)
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
    public static function error(string $message)
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
    public static function success(string $message)
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
    public static function info(string $message)
    {
        if (FLOW_SAPITYPE === 'CLI') {
            echo "\033[0;36m" . $message . "\033[0m" . PHP_EOL;
        }
    }

    /**
     * Outputs specified text to the console window
     * You can specify arguments that will be passed to the text via sprintf
     *
     * @see http://www.php.net/sprintf
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @return void
     * @api
     */
    public static function output($text, array $arguments = [])
    {
        if (self::$output === null) {
            return;
        }

        self::$output->output($text, $arguments);
    }

    /**
     * Outputs specified text to the console window and appends a line break
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @return void
     * @see output()
     * @see outputLines()
     * @api
     */
    public static function outputLine($text = '', array $arguments = [])
    {
        if (self::$output === null) {
            return;
        }

        self::$output->outputLine($text, $arguments);
    }

    /**
     * Formats the given text to fit into MAXIMUM_LINE_LENGTH and outputs it to the
     * console window
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @param integer $leftPadding The number of spaces to use for indentation
     * @return void
     * @see outputLine()
     * @api
     */
    public static function outputFormatted($text = '', array $arguments = [], $leftPadding = 0)
    {
        if (self::$output === null) {
            return;
        }

        self::$output->outputFormatted($text, $arguments, $leftPadding);
    }

    /**
     * Print a success message to the terminal.
     *
     * @param string $message The message to output
     * @return void
     */
    public static function outputSuccessMessage(string $message)
    {
        self::$outputLine("<fg=green>$message</>");
    }

    /**
     * Print a warning message to the terminal.
     *
     * @param string $message The message to output
     * @return void
     */
    public static function outputWarningMessage(string $message)
    {
        self::$outputLine("<fg=yellow>$message</>");
    }

    /**
     * Print an info message to the terminal.
     *
     * @param string $message The message to output
     * @return void
     */
    public static function outputInfoMessage(string $message)
    {
        self::$outputLine("<fg=cyan>$message</>");
    }

    /**
     * Print an error message to the terminal.
     *
     * @param string $message The message to output
     * @return void
     */
    public static function outputErrorMessage(string $message)
    {
        self::$outputLine("<fg=red;options=bold>$message</>");
    }

    /**
     * Ask the user for confirmation.
     *
     * @param string $question The question to ask for
     * @param bool $default The default answer if the user entered nothing
     * @return bool
     */
    public static function requireConfirmation(string $question, bool $default = false): bool
    {
        return self::$output->askConfirmation("<comment>$question [y/n]</> ", $default);
    }

    /**
     * Print a border to the terminal screen.
     *
     * @param string $pattern The pattern to repeat as border
     * @param int $margin The number of newlines to print above and below the border
     * @return void
     */
    public static function outputBorder(string $pattern = '-', int $margin = 0)
    {
        $maxLength = self::$output->getMaximumLineLength();

        if (mb_strlen($pattern) < $maxLength) {
            $multiplier = ceil($maxLength / mb_strlen($pattern));
            $pattern = str_repeat($pattern, $multiplier);
        }

        if (mb_strlen($pattern) > $maxLength) {
            $pattern = str_split($pattern, $maxLength)[0];
        }

        for ($i = 0; $i < $margin; $i++) {
            self::newline();
        }

        self::$outputLine($pattern);

        for ($i = 0; $i < $margin; $i++) {
            self::newline();
        }
    }

    /**
     * Prints a list of data to the terminal, padded like a two-column table.
     * Array keys are used as names for the values, thus pass readable keys here.
     *
     * @param array $data The data to display
     * @param string $connector The connector string to use for separation of keys and values
     * @return void
     */
    public static function outputDataList(array $data, string $connector = ':')
    {
        $maxLength = 0;
        foreach (array_keys($data) as $key) {
            $length = mb_strlen($key);

            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }

        foreach ($data as $key => $value) {
            $key = str_pad($key, $maxLength, ' ', STR_PAD_RIGHT);

            self::$output("<fg=cyan>$key</>");
            self::$output(" $connector ");
            self::$output("<fg=green>$value</>");
            self::$output(PHP_EOL);
        }
    }

    /**
     * Prints the given data as an unordered list to the terminal.
     *
     * @param array $data The list of data to display
     * @return void
     */
    public static function outputUnorderedList(array $data)
    {
        foreach ($data as $value) {
            self::$outputLine(' - ' . $value);
        }
    }

    /**
     * Prints the given data as an numbered list to the terminal.
     *
     * @param array $data The list of data to display
     * @return void
     */
    public static function outputOrderedList(array $data)
    {
        $maxNum = mb_strlen(strval(count($data) + 1)) + 3;

        foreach (array_values($data) as $index => $value) {
            $key = (' ' . ($index + 1) . ')');
            $key = str_pad($key, $maxNum, ' ', STR_PAD_RIGHT);

            self::$outputLine($key . $value);
        }
    }

    /**
     * Print a newline to the terminal.
     *
     * @param int $count The number of newlines to print
     * @return void
     */
    public static function newline(int $count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            self::$output(PHP_EOL);
        }
    }
}
