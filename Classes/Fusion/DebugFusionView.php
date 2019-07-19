<?php
namespace MCStreetguy\FusionLinter\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use MCStreetguy\FusionLinter\Factory\MockControllerContextFactory;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Core\Parser;

/**
 * Mock-View for Fusion rendering, manipulating several behaviours for improved debugging.
 */
class DebugFusionView extends FusionView
{
    /**
     * !Only present to override the parent type definition as it causes errors in the object management!
     * @var FusionView
     */
    protected $fallbackView;

    /**
     * @Flow\Inject
     * @var MockControllerContextFactory
     */
    protected $mockFactory;

    /**
     * @var array
     */
    protected $fusionCodeMap;

    public function initializeObject()
    {
        $this->fusionCodeMap = [];
        $this->controllerContext = $this->mockFactory->buildControllerContext();
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->initializeFusionRuntime();
        return $this->renderFusion();
    }

    /**
     * Retrieve the Fusion runtime engine.
     * 
     * @return Runtime
     */
    public function retrieveRuntime()
    {
        if ($this->fusionRuntime === null) {
            $this->initializeFusionRuntime();
        }

        return $this->fusionRuntime;
    }

    /**
     * Retrieve the Fusion parser.
     * 
     * @return Parser
     */
    public function retrieveParser()
    {
        return $this->fusionParser;
    }

    /**
     * Retrieve the Fusion source code map.
     * 
     * @return array
     */
    public function retrieveFusionCode()
    {
        return $this->fusionCodeMap;
    }

    /**
     * Retrieve the parsed Fusion object tree.
     * 
     * @return array
     */
    public function retrieveObjectTree()
    {
        return $this->parsedFusion;
    }

    /**
     * Load Fusion from the directories specified by $this->getOption('fusionPathPatterns')
     *
     * @return void
     */
    protected function loadFusion()
    {
        $this->parsedFusion = $this->getMergedFusionObjectTree();
    }

    /**
     * Parse all the fusion files the are in the current fusionPathPatterns
     *
     * @return array
     */
    protected function getMergedFusionObjectTree(): array
    {
        $parsedFusion = [];
        $fusionPathPatterns = $this->getOption('fusionPathPatterns');

        foreach ($fusionPathPatterns as $fusionPathPattern) {
            $fusionPathPattern = str_replace('@package', $this->getPackageKey(), $fusionPathPattern);

            if (is_dir($fusionPathPattern)) {
                $fusionPathPattern .= '/Root.fusion';
            }

            if (file_exists($fusionPathPattern)) {
                $content = file_get_contents($fusionPathPattern);
                $this->fusionCodeMap[$fusionPathPattern] = $content;

                $parsedFusion = $this->fusionParser->parse(file_get_contents($fusionPathPattern), $fusionPathPattern, $parsedFusion);
            }
        }

        return $parsedFusion;
    }
}
