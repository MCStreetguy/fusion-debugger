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
use MCStreetguy\FusionDebugger\Exceptions\AbstractDebuggerException;

/**
 * @Flow\Scope("singleton")
 */
class FusionCommandController extends AbstractCommandController
{
    const PROTOTYPES_KEY_NAME = '__prototypes';
    const RELATIVE_PATH_SUBTRACTOR_PATTERN = '/resource:\/\/[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+\//i';

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
     *
     * @param string $packageKey The package to load the Fusion code from. If not given, all packages are linted.
     * @param bool $verbose Produce more detailled output with additional information.
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
                $relativeFilePath = preg_replace(self::RELATIVE_PATH_SUBTRACTOR_PATTERN, '', $file->getFullPath());

                $this->outputErrorMessage("Error in $containingPackageKey -> '$relativeFilePath': {$e->getMessage()}");

                $errors++;
                continue;
            }

            if ($verbose === true) {
                $this->outputSuccessMessage("File {$file->getRelativePath()} contains no errors.");
            }
        }

        $this->newline();

        if ($errors > 0) {
            $this->outputWarningMessage("Processed $totalCount files and encountered $errors errors!");
            $this->outputWarningMessage('There may be additional output containing more information above.');
            $this->quit(1);
        }

        $this->outputSuccessMessage("Processed $totalCount files and found no syntax errors.");
        $this->quit(0);
    }

    /**
     * Show the merged fusion object tree.
     *
     * Builds the object tree from all fusion files and displays it in a tree structure.
     * Please not that this command does not reveal the __prototype key.
     *
     * @param string $path A fusion path to filter the object tree by
     * @return void
     * @see mcstreetguy.fusiondebugger:fusion:debugprototype
     */
    public function showObjectTreeCommand(string $path = null)
    {
        if ($path === '__prototypes') {
            $this->outputWarningMessage('Please use the fusion:showprototypehierachie command to debug Fusion prototypes!');
            $this->quit(1);
        }

        try {
            $objectTree = $this->debugger->getObjectTree($path);
        } catch (AbstractDebuggerException $e) {
            $this->outputErrorMessage($e->getMessage());
            $this->sendAndExit(round($e->getCode() % 255));
        }

        $tree = $this->debugger->buildVisualFusionTree($objectTree, ($path ?: '.'));

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
     * These additional behaviour can be suppressed by specifying the options --no-color or --not-flat
     * if it corrupts the resulting data or your terminal does not support ANSI colors.
     *
     * If you encounter a '(?)' sign after a further prototype name, this means that the named prototype
     * could not be found in the current prototype hierachie. These could probably cause rendering errors
     * if there really is no such prototype defined, but it may be that it's just a recognition error.
     * You should have a closer look on these properties and prototypes in either case!
     *
     * @param string $prototype The prototype to resolve the definition for
     * @param bool $noColor Suppress any colorized output
     * @param bool $notFlat Don't flatten the prototype definition array
     * @return void
     */
    public function debugPrototypeCommand(string $prototype, bool $noColor = false, bool $notFlat = false)
    {
        try {
            $definition = $this->debugger->loadPrototype($prototype);
        } catch (AbstractDebuggerException $e) {
            $this->outputErrorMessage($e->getMessage());
            $this->sendAndExit(round($e->getCode() % 255));
        }

        if ($notFlat === false) {
            $definition = $this->debugger->flattenPrototypeDefinition($definition);
        }

        $tree = $this->debugger->buildVisualFusionTree($definition, $prototype);

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
        } else {
            //TODO: We may not swallow remaining errors, output them too?
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
        ], [
            '<fg=red>$0</>',
            '<fg=magenta>\${$1}</>',
            '__objectType => <fg=yellow;options=bold>$1</>',
            '$1 <fg=yellow;options=bold>[$2]</>',
            '─ <fg=red>$1</>',
            '─ <fg=blue>$1</>',
            '<fg=green>$0</>',
            '<fg=red;options=reverse>(?)</>',
        ], $tree);
    }
}
