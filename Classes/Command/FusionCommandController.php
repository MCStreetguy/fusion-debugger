<?php
namespace MCStreetguy\FusionLinter\Command;

/*
 * This file is part of the MCStreetguy.FusionLinter package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use MCStreetguy\FusionLinter\Fusion\Utility\FusionFile;
use BlueM\Tree;
use Neos\Utility\Files;

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
     * @return void
     */
    public function lintCommand(string $packageKey = null)
    {
        $filesToLint = $this->loadFusionFiles($packageKey);

        $objectTree = [];
        foreach ($filesToLint as $file) {
            $fileTree = $this->fusionParser->parse(
                $file->getContents(),
                $file->getFullPath(),
                $objectTree,
                true
            );

            $this->outputSuccessMessage("File {$file->getRelativePath()} contains no errors.");
        }

        $treeStructure = new Tree($objectTree);
        \Kint::dump($treeStructure);
    }

    // Service methods

    /**
     * @return FusionFile[]
     */
    protected function loadFusionFiles(string $fromPackageKey = null)
    {
        $foundFusionFiles = [];

        if ((
            $fromPackageKey !== null &&
            $this->packageManager->isPackageKeyValid($fromPackageKey) &&
            $this->packageManager->isPackageAvailable($fromPackageKey)
        )) {
            $sourcePackages = [$this->packageManager->getPackage($fromPackageKey)];
        } else {
            $sourcePackages = $this->packageManager->getActivePackages();
        }

        /** @var PackageInterface $package */
        foreach ($sourcePackages as $package) {
            $packageKey = $package->getPackageKey();

            if ($this->packageManager->isPackageFrozen($packageKey)) {
                //* If the package is frozen it has no impact on fusion rendering and thus can safely be skipped
                continue;
            }

            $basePath = "resource://$packageKey/Private/Fusion";

            foreach ($this->settings['fusionFilePathPatterns'] as $basePathPattern) {
                $basePath = str_replace('@package', $packageKey, $basePathPattern);

                if (file_exists($basePath) && is_dir($basePath) && is_readable($basePath)) {
                    foreach (Files::readDirectoryRecursively($basePath, '.fusion') as $file) {
                        $foundFusionFiles[] = new FusionFile($basePath, $file);
                    }
                }
            }
        }

        //? Caching?

        return $foundFusionFiles;
    }
}
