<?php
namespace MCStreetguy\FusionDebugger\Command;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use MCStreetguy\FusionDebugger\Fusion\Debugger;
use MCStreetguy\FusionDebugger\Utility\FusionFileService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;

/**
 * @Flow\Scope("singleton")
 */
class FusionCommandController extends AbstractCommandController
{
    /**
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var Parser
     */
    protected $fusionParser;

    /**
     * @Flow\Inject
     * @var Debugger
     */
    protected $debugger;

    /**
     * @Flow\Inject
     * @var FusionFileService
     */
    protected $files;

    /**
     * @var Runtime
     */
    protected $fusionRuntime;

    // Commands

    /**
     * Lint the existing Fusion code.
     *
     * Lint the existing Fusion code.
     *
     * @param string $packageKey The package to load the Fusion code from.
     * @param bool $verbose Produce additional output with additional information.
     * @return void
     */
    public function lintCommand(string $packageKey = null, bool $verbose = false)
    {
        $filesToLint = $this->files->load($packageKey);
        $totalCount = count($filesToLint);
        $errors = 0;

        foreach ($filesToLint as $file) {
            try {
                $this->fusionParser->parse(
                    $file->getContents(),
                    $file->getFullPath()
                );
            } catch (Exception $e) {
                $containingPackageKey = $file->getPackageKey();
                $relativeFilePath = preg_replace('/resource:\/\/[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+\//i', '', $file->getFullPath());

                $this->outputErrorMessage("Error in $containingPackageKey -> '$relativeFilePath': {$e->getMessage()}");

                $errors++;
                continue;
            }

            if ($verbose === true) {
                $this->outputSuccessMessage("File {$file->getRelativePath()} contains no errors.");
            }
        }

        $this->newline();

        if ($errors <= 0) {
            $this->outputSuccessMessage("Processed $totalCount files and found no syntax errors.");
        } else {
            $this->outputWarningMessage("Processed $totalCount files and encountered $errors errors!");
            $this->outputWarningMessage('There may be additional output containing more information above.');
        }
    }

    /**
     * Show the merged fusion object tree.
     *
     * Show the merged fusion object tree.
     *
     * @param string $path A fusion path to filter the object tree by
     * @return void
     */
    public function showObjectTreeCommand(string $path = null)
    {
        if ($path === '__prototypes') {
            $this->outputWarningMessage('Please use the fusion:showprototypehierachie command to debug Fusion prototypes!');
            $this->quit(1);
        }

        $tree = $this->debugger->getObjectTree($path);
        $ascii = $this->buildAsciiTree($tree, ($path ?: '.'));

        $this->output(implode(PHP_EOL, $ascii));
        $this->newline();
    }

    /**
     * Show the merged fusion prototype configuration.
     *
     * Show the merged fusion prototype configuration.
     *
     * @param string $prototype The prototype to resolve the definition for
     * @param bool $noColor Suppress any colorized output
     * @param bool $notFlat Don't flatten the prototype definition array
     * @return void
     */
    public function debugPrototypeCommand(string $prototype, bool $noColor = false, bool $notFlat = false)
    {
        $definition = $this->debugger->loadPrototype($prototype);

        if ($notFlat === false) {
            $definition = $this->debugger->flattenPrototypeDefinition($definition);
        }

        $tree = $this->buildAsciiTree($definition, $prototype);

        if ($noColor === false) {
            $tree = $this->colorizeTree($tree);
        }

        $this->output(implode(PHP_EOL, $tree));
        $this->newline();
    }

    // Service methods

    /**
     * Output a short version of the given exception message.
     *
     * @param \Exception $e The source exception
     * @return void
     */
    protected function transformExceptionMessage(\Exception $e)
    {
        if ($e->getCode() === 1180600696 || $e->getCode() === 1180600697 || $e->getCode() === 1180547190) {
            /** @see https://github.com/neos/neos-development-collection/blob/master/Neos.Fusion/Classes/Core/Parser.php#L322 */
            /** @see https://github.com/neos/neos-development-collection/blob/master/Neos.Fusion/Classes/Core/Parser.php#L325 */
            /** @see https://github.com/neos/neos-development-collection/blob/master/Neos.Fusion/Classes/Core/Parser.php#L577 */
            $this->outputErrorMessage('Invalid namespace declaration given!');
        } elseif ($e->getCode() === 1180547966) {
            /** @see https://github.com/neos/neos-development-collection/blob/master/Neos.Fusion/Classes/Core/Parser.php#L384 */

            preg_match('/Syntax error in line (\d+). \((.+)\)/i', $e->getMessage(), $matches);
            $this->outputErrorMessage('Syntax error on line ' . $matches[1] . ': ' . $matches[2] . '!');
        } elseif ($e->getCode() === 1180615119) {
            /** @see https://github.com/neos/neos-development-collection/blob/master/Neos.Fusion/Classes/Core/Parser.php#L405 */
            $this->outputErrorMessage('Closed a block comment that has never been opened!');
        } elseif ($e->getCode() === 1180614895) {
            /** @see https://github.com/neos/neos-development-collection/blob/master/Neos.Fusion/Classes/Core/Parser.php#L416 */
            $this->outputErrorMessage('An internal parser error occurred! (Could not parse comment line properly)');
        } elseif ($e->getCode() === 1181575973) {
            /** @see https://github.com/neos/neos-development-collection/blob/master/Neos.Fusion/Classes/Core/Parser.php#L435 */
            $this->outputErrorMessage('More closing curly braces than opening ones!');
        } elseif ($e->getCode() === 1180544656) {
            /** @see https://github.com/neos/neos-development-collection/blob/master/Neos.Fusion/Classes/Core/Parser.php#L452 */

            preg_match('/Invalid declaration "(.+)"/i', $e->getMessage(), $matches);
            $this->outputErrorMessage('Unknown declaration given: ' . $matches[1] . '!');
        } elseif (in_array($e->getCode(), [
            1180548488,
            1358418019,
            1358418015,
        ])) { // no special treatment
            $this->outputErrorMessage($e->getMessage());
        }
    }

    /**
     * Print a recursive fusion tree structure with ASCII chars to the terminal.
     * Additionally this returns the tree as array of strings.
     *
     * @param array $data The associative data to display
     * @param string $root The root key to display on top of the tree (defaults to '.')
     * @param bool $suppressOutput Suppress any output (mainly used internally for recursive rendering)
     * @return array
     */
    protected function buildAsciiTree(array $data, string $root = '.')
    {
        $tree = [$root];
        $cycle = 0;
        $count = count($data);

        foreach ($data as $key => $value) {
            $prefix = '├── ';

            // Change box-decorator prefix if element is the last child
            if (($isLast = ($cycle === $count - 1)) === true) {
                $prefix = '└── ';
            }

            $type = gettype($value);

            if ($type === 'array') { // Render the tree for the nested array and append it to the current
                $isFirst = true;
                $nestedTree = $this->buildAsciiTree($value, $key);

                foreach ($nestedTree as $nestedLine) {
                    if ($isFirst === true) { // Don't indent the first line of the tree as we already have proper indentation
                        $tree[] = $prefix . $nestedLine;
                    } elseif ($isLast === true) { // Prepend the nested line with 4 spaces as there is no further parent-sibling
                        $tree[] = '    ' . $nestedLine;
                    } else { // Prepend the nested line with a box-decorator and 3 spaces as there are more parent-siblings to render
                        $tree[] = '│   ' . $nestedLine;
                    }

                    $isFirst = false;
                }
            } elseif ($type === 'object') { // Render a static label with the classname of the object
                $tree[] = $prefix . $key . ' => object<' . get_class($value) . '>';
            } elseif ($value === null) { // Special treatment for values that are explicitly 'null'
                $tree[] = $prefix . $key . ' => null';
            } elseif ($value === false) { // Special treatment for values that are explicitly 'null'
                $tree[] = $prefix . $key . ' => false';
            } elseif (empty($value)) { // Render a placeholder to show that the key is explictly empty
                $tree[] = $prefix . $key . ' => <empty>';
            } elseif ($key === '__eelExpression' && substr($value, 0, 2) !== '${') { // Surround eel expressions with '${...}' to make them look like such
                $tree[] = $prefix . $key . ' => ${' . $value . '}';
            } elseif ($type === 'string' && $key !== '__objectType' && substr($value, 0, 2) !== '${') { // Sourround strings that are not object names with quotation marks
                $tree[] = $prefix . $key . ' => "' . $value . '"';
            } else { // Render the static 'key => value' label for all other cases
                $tree[] = $prefix . $key . ' => ' . $value;
            }

            $cycle++;
        }

        // Return the tree as array of strings for internal further use
        return $tree;
    }

    /**
     * Add colors to the ascii tree structure by regex replacements.
     *
     * @param array $tree The source ascii tree, line by line
     * @return array The parsed ascii tree, line by line
     */
    protected function colorizeTree(array $tree)
    {
        return preg_replace([
            '/@[\w.:-]+/', // 1. meta properties
            '/\$\{(.+)\}/', // 2. EEL expressions
            '/(\w+) \[([a-zA-Z0-9.:]+)\]/', // 3. Object names
            '/─ (__[\w.:-]+)/', // 4. internal properties
            '/─ ([\w.:-]+)/', // 5. other properties
            '/".+"/', // 6. string values
        ], [
            '<fg=red>$0</>',
            '<fg=magenta>\${$1}</>',
            '$1 <fg=yellow>[$2]</>',
            '─ <fg=red>$1</>',
            '─ <fg=blue>$1</>',
            '<fg=green>$0</>',
        ], $tree);
    }
}
