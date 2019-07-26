<?php
namespace MCStreetguy\FusionDebugger\Fusion\Utility;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\Files as FilesUtility;

/**
 * @Flow\Scope("singleton")
 */
class Files
{
    /**
     * Simple "cache" for found fusion files
     * @var array
     */
    protected $files = [];

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
     * @return FusionFile[]
     */
    public function load(string $fromPackageKey = null)
    {
        $staticCacheKey = '*';
        $foundFusionFiles = [];

        if ($fromPackageKey !== null && array_key_exists($fromPackageKey, $this->files)) {
            return $this->files[$fromPackageKey];
        } elseif (array_key_exists($staticCacheKey, $this->files)) {
            return $this->files[$staticCacheKey];
        }

        if ((
            $fromPackageKey !== null &&
            $this->packageManager->isPackageKeyValid($fromPackageKey) &&
            $this->packageManager->isPackageAvailable($fromPackageKey)
        )) {
            $sourcePackages = [$this->packageManager->getPackage($fromPackageKey)];
            $staticCacheKey = $fromPackageKey;
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

            foreach ($this->settings['fusionFilePathPatterns'] as $basePathPattern) {
                $basePath = str_replace('@package', $packageKey, $basePathPattern);

                if (file_exists($basePath) && is_dir($basePath) && is_readable($basePath)) {
                    foreach (FilesUtility::readDirectoryRecursively($basePath, '.fusion') as $file) {
                        $foundFusionFiles[] = new FusionFile($packageKey, $file);
                    }
                }
            }
        }

        $this->files[$staticCacheKey] = $foundFusionFiles;

        return $foundFusionFiles;
    }
}
