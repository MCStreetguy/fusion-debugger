<?php
namespace MCStreetguy\FusionLinter\Command;

/*
 * This file is part of the MCStreetguy.FusionLinter package.
 */

use MCStreetguy\FusionLinter\Traits\AdvancedConsoleLogger;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\PackageManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class FusionCommandController extends CommandController
{
    use AdvancedConsoleLogger;

    const EC_INVALID_KEY = 2;
    const EC_INACTIVE_PACKAGE = 3;
    const EC_MISSING_PACKAGE = 4;
    const EC_FROZEN_PACKAGE = 5;

    /**
     * @var PackageManagerInterface
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @var array
     * @Flow\InjectConfiguration
     */
    protected $settings;

    /**
     * Lint the existing Fusion code.
     *
     * Lint the existing Fusion code.
     *
     * @param string $packageKey The package to load the Fusion code from. Package must be active and not frozen.
     * @param string $path The fusion path to be linted. If ommitted, the standard '/root' path is used.
     * @return void
     */
    public function lintCommand(string $packageKey, string $path = null)
    {
        if (!$this->packageManager->isPackageKeyValid($packageKey)) {
            $this->outputErrorMessage("Package key '$packageKey' is invalid!");
            $this->quit(self::EC_INVALID_KEY);
        } elseif (!$this->packageManager->isPackageActive($packageKey)) {
            if ($this->packageManager->isPackageAvailable($packageKey)) {
                $this->outputWarningMessage("Package '$packageKey' is not active but available. However only active packages can be linted.");
                $this->outputInfoMessage("You may want to activate the package with './flow flow:package:activate $packageKey' and try again afterwards.");
                $this->quit(self::EC_INACTIVE_PACKAGE);
            }

            $this->outputErrorMessage("Package '$packageKey' could not be found!");
            $this->quit(self::EC_MISSING_PACKAGE);
        } elseif ($this->packageManager->isPackageFrozen($packageKey)) {
            $this->outputWarningMessage("Package '$packageKey' is frozen and thus cannot be linted.");
            $this->outputInfoMessage("You may want to unfreeze the package with './flow flow:package:unfreeze $packageKey' and try again afterwards.");
            $this->quit(self::EC_FROZEN_PACKAGE);
        }

        $this->fusionView->setPackageKey($packageKey);
        $this->fusionView->setFusionPathPattern("resource://$packageKey/Private/Fusion");
        $this->fusionView->setFusionPath($path ?? $this->settings['defaultFusionPath']);

        $this->fusionView->render();

        $this->outputSuccessMessage('No error has been found, your Fusion code seems valid.');
        $this->quit();
    }
}
