<?php
namespace MCStreetguy\FusionDebugger\Fusion;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use Neos\Flow\Annotations as Flow;
use MCStreetguy\FusionDebugger\Fusion\Utility\Files;
use Neos\Fusion\Core\Parser;
use MCStreetguy\FusionDebugger\Exceptions\FusionParseErrorException;
use MCStreetguy\FusionDebugger\Exceptions\MissingPrototypeDefinitionException;
use Neos\Utility\Arrays;

/**
 * @Flow\Scope("singleton")
 */
class Debugger
{
    const EXPRESSION_KEY = '__eelExpression';
    const OBJECT_TYPE_KEY = '__objectType';
    const PROTOTYPE_CHAIN_KEY = '__prototypeChain';
    const PROTOTYPES_KEY = '__prototypes';
    const VALUE_KEY = '__value';

    /**
     * @Flow\Inject
     * @var Files
     */
    protected $files;

    /**
     * @Flow\Inject
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $fusionTree;

    /**
     *
     */
    public function loadPrototype(string $name)
    {
        // Load and combine a single prototype defintion

        $prototypes = $this->loadFusionTree()[self::PROTOTYPES_KEY];

        if (!array_key_exists($name, $prototypes)) {
            throw MissingPrototypeDefinitionException::forPrototypeName($name);
        }

        $bareDefinition = $prototypes[$name];
        $mergedDefinition = $this->mergePrototypeChain($bareDefinition);

        return $mergedDefinition;
    }

    public function loadAllDefinitions()
    {
        // Load (and combine?) all prototype definitions
    }

    public function getObjectTree(string $path = null)
    {
        // Retrieve the object tree, optionally filtered by the given path
    }

    // Helper methods

    /**
     * Load the fusion tree from all collected files.
     * If this has already be done, the previous result is returned for improved performance.
     *
     * @return array The loaded fusion tree
     * @throws FusionParseErrorException
     */
    protected function loadFusionTree()
    {
        if (!empty($this->fusionTree)) {
            return $this->fusionTree;
        }

        foreach ($this->files->load() as $file) {
            try {
                $this->fusionTree = $this->parser->parse(
                    $file->getContents(),
                    $file->getFullPath(),
                    $this->fusionTree
                );
            } catch (\Throwable $e) {
                throw FusionParseErrorException::forFile($file->getFullPath(), $e);
            }
        }

        return $this->fusionTree;
    }

    /**
     * Retrieve all parent prototyps from the inheritance chain and merge their configuration together.
     *
     * @param array $definition The bar definition to merge
     * @return array The merged definition
     */
    protected function mergePrototypeChain(array $definition)
    {
        if (empty($definition) || !array_key_exists(self::PROTOTYPE_CHAIN_KEY, $definition)) {
            return $definition;
        }

        foreach ($definition[self::PROTOTYPE_CHAIN_KEY] as $chainedPrototype) {
            $chainedDefinition = $this->loadPrototype($chainedPrototype);
            $definition = Arrays::arrayMergeRecursiveOverrule($definition, $chainedDefinition, false, false);
        }

        return $definition;
    }
}

