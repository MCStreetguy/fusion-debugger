<?php
namespace MCStreetguy\FusionLinter\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\Parser;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Package\PackageInterface;

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
    protected function loadFusionFiles()
    {
        $sourcePackages = $this->packageManager->getActivePackages();

        /** @var PackageInterface $package */
        foreach ($sourcePackages as $package) {
            $packageKey = $package->getPackageKey();

            if ($this->packageManager->isPackageFrozen($packageKey)) {
                //* If the package is frozen it has no impact on fusion rendering thus can be skipped
                continue;
            }
        }
    }
}