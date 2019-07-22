<?php
namespace MCStreetguy\FusionLinter\Fusion;

use MCStreetguy\FusionLinter\Fusion\Utility\FusionFile;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use Neos\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class Debugger
{
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
     *
     */
    public function loadFusionFiles()
    {
        $foundFusionFiles = [];
        $sourcePackages = $this->packageManager->getActivePackages();

        /** @var PackageInterface $package */
        foreach ($sourcePackages as $package) {
            $packageKey = $package->getPackageKey();

            if ($this->packageManager->isPackageFrozen($packageKey)) {
                //* If the package is frozen it has no impact on fusion rendering and thus can safely be skipped
                continue;
            }

            $basePath = "resource://$packageKey/Private/Fusion";

            if (file_exists($basePath) && is_dir($basePath) && is_readable($basePath)) {
                foreach (Files::readDirectoryRecursively($basePath, '.fusion') as $file) {
                    $foundFusionFiles[] = new FusionFile($file);
                }
            }
        }

        //? Caching?

        return $foundFusionFiles;
    }
}
