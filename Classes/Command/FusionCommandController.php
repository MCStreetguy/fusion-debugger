<?php
namespace MCStreetguy\FusionDebugger\Command;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use MCStreetguy\FusionDebugger\Exceptions\AbstractDebuggerException;
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
    const PROTOTYPES_KEY_NAME = '__prototypes';

    const RELATIVE_PATH_SUBTRACTOR_PATTERN = '/resource:\/\/[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+\//i';
    const FUSION_INCLUDE_STATEMENT_PATTERN = '/^include:/im';

    const FUSION_INCLUDE_STATEMENT_REPLACEMENT = '#include:';

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
     * Checks all Fusion files individually for syntax errors and lists the
     * incorrect files with their associated package, file path and contained error.
     * The command exits with code '0' if no error has been found, '1' otherwise.
     *
     * @param string $packageKey The package to load the Fusion code from. If not given, all packages are linted.
     * @param bool $verbose Produce more detailled output with additional information.
     * @param bool $quiet Don't produce any output. Overrules the --verbose flag.
     * @return void
     */
    public function lintCommand(string $packageKey = null, bool $verbose = false, bool $quiet = false)
    {
        $filesToLint = $this->files->load($packageKey);
        $totalCount = count($filesToLint);
        $errors = [];

        if (!$quiet && !$verbose) {
            $this->output->progressStart($totalCount);
        }

        foreach ($filesToLint as $file) {
            try {
                $fileContents = $file->getContents();

                /**
                 * Normalized file contents without any 'include:' statement to prevent doubled error messages.
                 * @see https://github.com/MCStreetguy/fusion-debugger/issues/4
                 * @var string
                 */
                $normalizedFileContents = preg_replace(
                    self::FUSION_INCLUDE_STATEMENT_PATTERN,
                    self::FUSION_INCLUDE_STATEMENT_REPLACEMENT,
                    $fileContents
                );

                $this->fusionParser->parse(
                    $normalizedFileContents,
                    $file->getFullPath()
                );
            } catch (Exception $e) {
                $containingPackageKey = $file->getPackageKey();
                $relativeFilePath = preg_replace(self::RELATIVE_PATH_SUBTRACTOR_PATTERN, '', $file->getFullPath());

                $errors[] = "Error in $containingPackageKey -> '$relativeFilePath': {$e->getMessage()}";

                !$quiet && $this->output->progressAdvance();
                continue;
            }

            if ($verbose && !$quiet) {
                $this->outputSuccessMessage("File {$file->getRelativePath()} contains no errors.");
            } elseif (!$quiet) {
                $this->output->progressAdvance();
            }
        }

        $errorCount = count($errors);

        if (!$quiet) {
            if (!$verbose) {
                $this->output->progressFinish();
            }

            $this->newline();

            foreach ($errors as $errorMessage) {
                $this->outputErrorMessage($errorMessage);
            }

            $this->newline();
        }

        if ($errorCount > 0) {
            if (!$quiet) {
                $this->outputWarningMessage("Processed $totalCount files and encountered $errorCount errors!");
                $this->outputWarningMessage('There may be additional output containing more information above.');
            }

            $this->quit(1);
        } elseif (!$quiet) {
            $this->outputSuccessMessage("Processed $totalCount files and found no syntax errors.");
        }

        $this->quit(0);
    }

    /**
     * Show the merged fusion object tree.
     *
     * Builds the object tree from all fusion files and displays it in a tree structure.
     * Please note that this command does not reveal the __prototype key.
     *
     * For better readability, this command also includes something similar to syntax highlighting
     * as several parts of the built tree are colored (such as eel expressions, further prototype names
     * or just plain strings). Furthermore it flattens the resulting data by removing empty properties
     * and combining the internal properties for e.g. plain values (as these are stored with three properties
     * but could be displayed directly without an array structure). The resulting tree is sorted recursively
     * by the positional property '@position' if it is present, while meta keys get shifted to the beginning.
     * These additional behaviour can be suppressed by specifying the options --no-color or --no-flatten
     * if it corrupts the resulting data or your terminal does not support ANSI colors.
     *
     * If you encounter a '(?)' sign after a further prototype name, this means that the named prototype
     * could not be found in the current prototype hierachie. These could probably cause rendering errors
     * if there really is no such prototype defined, but it may be that it's just a recognition error.
     * You should have a closer look on these properties and prototypes in either case!
     *
     * @param string $path A fusion path to filter the object tree by
     * @param bool $noColor Suppress any colorized output
     * @param bool $noFlatten Don't flatten the prototype definition array
     * @return void
     * @see mcstreetguy.fusiondebugger:fusion:debugprototype
     */
    public function showObjectTreeCommand(string $path = null, bool $noColor = false, bool $noFlatten = false)
    {
        if ($path === '__prototypes') {
            $this->outputWarningMessage('Please use the fusion:debugprototype command to debug Fusion prototypes!');
            $this->quit(1);
        }

        try {
            $objectTree = $this->debugger->getObjectTree($path);
        } catch (AbstractDebuggerException $e) {
            $this->outputErrorMessage($e->getMessage());
            $this->sendAndExit(round($e->getCode() % 255));
        }

        if ($noFlatten === false) {
            $objectTree = $this->debugger->flattenPrototypeDefinition($objectTree);
        }

        $treeRoot = $path ?: '.';
        if ($noColor === false) {
            $treeRoot = '<fg=yellow;options=bold>' . $treeRoot . '</>';
        }

        $tree = $this->debugger->buildVisualFusionTree($objectTree, $treeRoot);

        if ($noColor === false) {
            $tree = $this->colorizeTree($tree);
        }

        $this->output(implode(PHP_EOL, $tree));
        $this->newline();
    }

    /**
     * Show the merged fusion prototype configuration.
     *
     * Reads the definition of the requested prototype from the '__prototypes' key in the parsed
     * fusion object tree and resolves the contained prototype chain very carefully so that the result
     * contains all properties, either inherited or explictely defined.
     *
     * For better readability, this command also includes something similar to syntax highlighting
     * as several parts of the built tree are colored (such as eel expressions, further prototype names
     * or just plain strings). Furthermore it flattens the resulting data by removing empty properties
     * and combining the internal properties for e.g. plain values (as these are stored with three properties
     * but could be displayed directly without an array structure). The resulting tree is sorted recursively
     * by the positional property '@position' if it is present, while meta keys get shifted to the beginning.
     * These additional behaviour can be suppressed by specifying the options --no-color or --no-flatten
     * if it corrupts the resulting data or your terminal does not support ANSI colors.
     *
     * If you encounter a '(?)' sign after a further prototype name, this means that the named prototype
     * could not be found in the current prototype hierachie. These could probably cause rendering errors
     * if there really is no such prototype defined, but it may be that it's just a recognition error.
     * You should have a closer look on these properties and prototypes in either case!
     *
     * @param string $prototype The prototype to resolve the definition for
     * @param bool $noColor Suppress any colorized output
     * @param bool $noFlatten Don't flatten the prototype definition array
     * @return void
     */
    public function debugPrototypeCommand(string $prototype, bool $noColor = false, bool $noFlatten = false)
    {
        try {
            $definition = $this->debugger->loadPrototype($prototype);
        } catch (AbstractDebuggerException $e) {
            $this->outputErrorMessage($e->getMessage());
            $this->sendAndExit(round($e->getCode() % 255));
        }

        if ($noFlatten === false) {
            $definition = $this->debugger->flattenPrototypeDefinition($definition);
        }

        $treeRoot = $prototype;
        if ($noColor === false) {
            $treeRoot = '<fg=yellow;options=bold>' . $treeRoot . '</>';
        }

        $tree = $this->debugger->buildVisualFusionTree($definition, $treeRoot);

        if ($noColor === false) {
            $tree = $this->colorizeTree($tree);
        }

        $this->output(implode(PHP_EOL, $tree));
        $this->newline();
    }

    /**
     * List all known prototypes by their names.
     *
     * List all known prototypes by their names.
     *
     * @param bool $noFormat Don't format the output as list
     * @param bool $noSort Don't sort the prototype list by name
     * @return void
     */
    public function listPrototypesCommand(bool $noFormat = false, bool $noSort = false)
    {
        if (!$noSort) {
            \natsort($prototypeNames);
        }

        if ($noFormat === false) {
            $this->outputUnorderedList($prototypeNames);
            $this->quit(0);
        }

        foreach ($prototypeNames as $name) {
            $this->outputLine($name);
        }
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
        } else {
            $this->outputErrorMessage('[UNKNOWN] ' . $e->getMessage());
        }
    }

    /**
     * Add colors to the rendered tree structure by regex replacements.
     *
     * @param array $tree The source tree, line by line
     * @return array The parsed tree, line by line
     */
    protected function colorizeTree(array $tree)
    {
        return preg_replace([
            '/@[\w.:-]+/', // meta properties
            '/\$\{(.+)\}/', // EEL expressions
            '/__objectType => (?!null)([a-zA-Z0-9.:]+)/', // Object types
            '/([\w-]+(?> =>)?) \[([a-zA-Z0-9.:]+)\]/', // Prototype names
            '/─ (__[\w.:-]+)/', // internal properties
            '/─ ([\w.:-]+)/', // other properties
            '/".+"/', // string values
            '/\(\?\)$/', // unknown prototype question marks
            '/ (true|false)$/', // boolean values
        ], [
            '<fg=red>$0</>',
            '<fg=magenta>\${$1}</>',
            '__objectType => <fg=yellow;options=bold>$1</>',
            '$1 <fg=yellow;options=bold>[$2]</>',
            '─ <fg=red>$1</>',
            '─ <fg=blue>$1</>',
            '<fg=green>$0</>',
            '<fg=red;options=reverse>(?)</>',
            ' <fg=green>$1</>',
        ], $tree);
    }
}
