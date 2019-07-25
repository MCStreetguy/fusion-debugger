<?php
namespace MCStreetguy\FusionLinter\Command;

use Neos\Flow\Cli\CommandController;
use MCStreetguy\FusionLinter\Service\IO;

/**
 *
 */
abstract class AbstractCommandController extends CommandController
{
    public function initializeObject()
    {
        IO::injectConsoleOutput($this->output);
    }

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
    protected function output($text, array $arguments = [])
    {
        $this->output->output($text, $arguments);
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
    protected function outputLine($text = '', array $arguments = [])
    {
        $this->output->outputLine($text, $arguments);
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
    protected function outputFormatted($text = '', array $arguments = [], $leftPadding = 0)
    {
        $this->output->outputFormatted($text, $arguments, $leftPadding);
    }

    /**
     * Print a success message to the terminal.
     *
     * @param string $message The message to output
     * @return void
     */
    protected function outputSuccessMessage(string $message)
    {
        $this->outputLine("<fg=green>$message</>");
    }

    /**
     * Print a warning message to the terminal.
     *
     * @param string $message The message to output
     * @return void
     */
    protected function outputWarningMessage(string $message)
    {
        $this->outputLine("<fg=yellow>$message</>");
    }

    /**
     * Print an info message to the terminal.
     *
     * @param string $message The message to output
     * @return void
     */
    protected function outputInfoMessage(string $message)
    {
        $this->outputLine("<fg=cyan>$message</>");
    }

    /**
     * Print an error message to the terminal.
     *
     * @param string $message The message to output
     * @return void
     */
    protected function outputErrorMessage(string $message)
    {
        $this->outputLine("<fg=red;options=bold>$message</>");
    }

    /**
     * Ask the user for confirmation.
     *
     * @param string $question The question to ask for
     * @param bool $default The default answer if the user entered nothing
     * @return bool
     */
    protected function requireConfirmation(string $question, bool $default = false): bool
    {
        return $this->output->askConfirmation("<comment>$question [y/n]</> ", $default);
    }

    /**
     * Print a border to the terminal screen.
     *
     * @param string $pattern The pattern to repeat as border
     * @param int $margin The number of newlines to print above and below the border
     * @return void
     */
    protected function outputBorder(string $pattern = '-', int $margin = 0)
    {
        $maxLength = $this->output->getMaximumLineLength();

        if (mb_strlen($pattern) < $maxLength) {
            $multiplier = ceil($maxLength / mb_strlen($pattern));
            $pattern = str_repeat($pattern, $multiplier);
        }

        if (mb_strlen($pattern) > $maxLength) {
            $pattern = str_split($pattern, $maxLength)[0];
        }

        for ($i = 0; $i < $margin; $i++) {
            $this->newline();
        }

        $this->outputLine($pattern);

        for ($i = 0; $i < $margin; $i++) {
            $this->newline();
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
    protected function outputDataList(array $data, string $connector = ':')
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

            $this->output("<fg=cyan>$key</>");
            $this->output(" $connector ");
            $this->output("<fg=green>$value</>");
            $this->output(PHP_EOL);
        }
    }

    /**
     * Prints the given data as an unordered list to the terminal.
     *
     * @param array $data The list of data to display
     * @return void
     */
    protected function outputUnorderedList(array $data)
    {
        foreach ($data as $value) {
            $this->outputLine(' - ' . $value);
        }
    }

    /**
     * Prints the given data as an numbered list to the terminal.
     *
     * @param array $data The list of data to display
     * @return void
     */
    protected function outputOrderedList(array $data)
    {
        $maxNum = mb_strlen(strval(count($data) + 1)) + 3;

        foreach (array_values($data) as $index => $value) {
            $key = (' ' . ($index + 1) . ')');
            $key = str_pad($key, $maxNum, ' ', STR_PAD_RIGHT);

            $this->outputLine($key . $value);
        }
    }

    /**
     * Print a newline to the terminal.
     *
     * @param int $count The number of newlines to print
     * @return void
     */
    protected function newline(int $count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->output(PHP_EOL);
        }
    }

    /**
     * Print a recursive tree structure to the terminal.
     */
    protected function outputTree(array $data, string $root = '.', bool $suppressOutput = false)
    {
        $tree = [$root];
        $cycle = 0;
        $count = count($data);

        foreach ($data as $key => $value) {
            $prefix = '├── ';
            $isLast = ($cycle === $count - 1);

            if ($isLast === true) {
                $prefix = '└── ';
            }

            $type = gettype($value);

            if ($type === 'array') {
                $isFirst = true;

                foreach ($this->outputTree($value, $key, true) as $nestedLine) {
                    if ($isFirst === true) {
                        $tree[] = $prefix . $nestedLine;
                    } elseif ($isLast === true) {
                        $tree[] = '    ' . $nestedLine;
                    } else {
                        $tree[] = '|   ' . $nestedLine;
                    }

                    $isFirst = false;
                }
            } elseif ($type === 'object') {
                $tree[] = $prefix . $key . ' => object<' . get_class($value) . '>';
            } else {
                $tree[] = $prefix . $key . ' => ' . $value;
            }

            $cycle++;
        }

        if ($suppressOutput === false) {
            $tree[] = '';
            $this->output(implode(PHP_EOL, $tree));
        }

        return $tree;
    }
}
