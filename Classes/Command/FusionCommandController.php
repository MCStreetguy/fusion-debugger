<?php
namespace MCStreetguy\FusionLinter\Command;

/*
 * This file is part of the MCStreetguy.FusionLinter package.
 */

use MCStreetguy\FusionLinter\Service\IO;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\PackageManagerInterface;
use MCStreetguy\FusionLinter\Fusion\Debugger;

/**
 * @Flow\Scope("singleton")
 */
class FusionCommandController extends CommandController
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
     * @var Debugger
     */
    protected $debugger;

    public function initializeObject()
    {
        IO::injectConsoleOutput($this->output);
    }

    /**
     * Lint the existing Fusion code.
     *
     * Lint the existing Fusion code.
     *
     * @param string $packageKey The package to load the Fusion code from. Package must be active and not frozen.
     * @param string $path The fusion path to be linted. If ommitted, the standard '/root' path is used.
     * @return void
     */
    public function lintCommand()
    {
        \Kint::dump($this->debugger->loadFusionFiles());
    }
}
