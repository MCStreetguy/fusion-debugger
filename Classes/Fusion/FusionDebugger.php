<?php
namespace MCStreetguy\FusionLinter\Fusion;

use MCStreetguy\FusionLinter\Fusion\Utility\FusionFile;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Utility\Files;
use MCStreetguy\FusionLinter\Service\IO;

/**
 * @Flow\Scope("singleton")
 */
class FusionDebugger
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

    /**
     * Load all available Fusion files.
     *
     * @return FusionFile[]
     */
    public function loadFusionFiles(string $fromPackageKey = null)
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

    /**
     * Lint fusion files.
     *
     * @param FusionFile[] $files (optional) The files to lint
     * @return null
     */
    public function lint(array $files = [])
    {
        if (empty($files)) {
            $files = $this->loadFusionFiles();
        }

        $internalObjectTree = [];

        foreach ($files as $file) {
            $fileObjectTree = $this->fusionParser->parse(
                $file->getContents(),
                $file->getFullPath(),
                $internalObjectTree,
                true
            );

            IO::outputSuccessMessage("File {$file->getRelativePath()} contains no syntax errors.");
        }
    }
}
