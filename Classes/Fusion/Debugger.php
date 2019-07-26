<?php
namespace MCStreetguy\FusionDebugger\Fusion;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use MCStreetguy\FusionDebugger\Exceptions\FusionParseErrorException;
use MCStreetguy\FusionDebugger\Exceptions\MissingPrototypeDefinitionException;
use MCStreetguy\FusionDebugger\Utility\FusionFileService;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Parser;
use Neos\Utility\Arrays;

/**
 * The main debugging component for Fusion.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Debugger
{
    const EXPRESSION_KEY = '__eelExpression';
    const META_INFO_KEY = '__meta';
    const OBJECT_TYPE_KEY = '__objectType';
    const PROTOTYPE_CHAIN_KEY = '__prototypeChain';
    const PROTOTYPE_OBJECT_NAME_KEY = '__prototypeObjectName';
    const PROTOTYPES_KEY = '__prototypes';
    const VALUE_KEY = '__value';

    /**
     * @Flow\Inject
     * @var FusionFileService
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
    protected $fusionTree = [];

    /**
     * Check if the named prototype is known by the fusion runtime.
     *
     * @param string $name The prototype name to check
     * @return bool 'true' if the prototype could be found, 'false' otherwise
     */
    public function isPrototypeKnown(string $name)
    {
        return array_key_exists($name, $this->loadFusionTree()[self::PROTOTYPES_KEY]);
    }

    /**
     * Load a full prototype definition by it's name.
     *
     * @param string $name The prototype name to retrieve
     * @param bool $returnBare Return the plain definition and don't merge anything
     * @return array The merged prototype definition
     */
    public function loadPrototype(string $name, bool $returnBare = false)
    {
        $prototypes = $this->loadFusionTree()[self::PROTOTYPES_KEY];

        if (!array_key_exists($name, $prototypes)) {
            throw MissingPrototypeDefinitionException::forPrototypeName($name);
        } elseif ($returnBare === true) {
            return $prototypes[$name];
        }

        return $this->mergePrototypeChain($name, $prototypes[$name]);
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

    protected function mergePrototypeChain(string $basePrototype, array $bareDefinition)
    {
        if (empty($bareDefinition) || !array_key_exists(self::PROTOTYPE_CHAIN_KEY, $bareDefinition)) {
            return $bareDefinition;
        }

        // Read the root prototype from the chain and use that as starting point,
        // as inheritance simply does not work the other way round
        $prototypeChain = $bareDefinition[self::PROTOTYPE_CHAIN_KEY];
        $rootPrototype = array_shift($prototypeChain);

        // If the base prototype is also the root prototype we already have its definition
        $definition = $bareDefinition;
        if ($rootPrototype !== $basePrototype) {
            $definition = $this->loadPrototype($rootPrototype);
        }

        // If there are still prototypes in the chain iterate all of them and merge their definitions
        if (count($prototypeChain) > 0) {
            foreach ($prototypeChain as $prototype) {
                $chainedDefinition = $this->loadPrototype($prototype);
                $definition = $this->mergePrototypeDefinitions($definition, $chainedDefinition);
            }
        }

        // If the root prototype differs from the base prototype we want to resolve
        // we finally need to merge the initial definition.
        // We can't do this inside of the loop as that would cause it to run eternally..
        if ($rootPrototype !== $basePrototype) {
            $definition = $this->mergePrototypeDefinitions($definition, $bareDefinition);
        }

        // Remove the prototype chain from the prototype definition as we just resolved it completely
        unset($definition[self::PROTOTYPE_CHAIN_KEY]);

        //? Maybe sort the properties recursively for better readability?
        // Arrays::sortKeysRecursively($definition, \SORT_NATURAL);

        return $definition;
    }

    /**
     * Helper method for merging two fusion prototype definitions.
     *
     * @param array $baseDefinition The prototype definition that shall be extended
     * @param array $extenderDefinition The prototype definition that shall be merged
     * @return array The merged prototype definition
     */
    protected function mergePrototypeDefinitions(array $baseDefinition, array $extenderDefinition)
    {
        foreach ($extenderDefinition as $key => $value) {
            if (is_object($value)) {
                // Should not happen normally but just in case we receive an object we'll convert it to an array to prevent errors
                $value = Arrays::convertObjectToArray($value);
            }

            if (!array_key_exists($key, $baseDefinition) || !is_array($value)) {
                // Key does not exist in definition or the value is a simple type -> set directly
                $baseDefinition[$key] = $value;
            } elseif (array_key_exists(self::OBJECT_TYPE_KEY, $value)) {
                // Key is present in definition but replaces previous value -> override
                $baseDefinition[$key] = $value;
            } else {
                // Key is present in definition and extends previous value -> merge recursively
                $baseDefinition[$key] = $this->mergePrototypeDefinitions($baseDefinition[$key], $value);
            }
        }

        return $baseDefinition;
    }

    /**
     * Flatten a fusion prototype definition for improved readability.
     *
     * This method removes all empty values from the resulting definition recursively.
     * Additionally extended structural definitions for actual simple types are reduced.
     *
     * If you set a fusion property to a simple value like this:
     * `foo = "bar"`
     * it will be internally stored as
     * ```
     * foo {
     *   __value = "bar"
     *   __objectName = null
     *   __eelExpression = null
     * }
     * ```
     * which is neither pretty or useful.
     *
     * @param array $definition The prototype definition to flatten.
     * @return array The flattened prototype definition
     */
    public function flattenPrototypeDefinition(array $definition)
    {
        $results = [];

        foreach ($definition as $key => &$value) {
            if (is_object($value)) {
                // Should not happen normally but just in case we receive an object we'll convert it to an array to prevent errors
                $value = Arrays::convertObjectToArray($value);
            } elseif (!is_array($value)) {
                // We have a simple value already, no special treatment required
                $results[$key] = $value;
                continue;
            }

            // If we have a simple type structure and no further properties we can convert that directly
            if ((
                count($value) === 3 &&
                array_key_exists(self::OBJECT_TYPE_KEY, $value) &&
                array_key_exists(self::EXPRESSION_KEY, $value) &&
                array_key_exists(self::VALUE_KEY, $value)
            )) {
                if (!empty($value[self::OBJECT_TYPE_KEY])) {
                    // We have a nested prototype without modification, so we just state that out
                    $tmpValue = 'prototype(' . $value[self::OBJECT_TYPE_KEY] . ')';
                    if (!$this->isPrototypeKnown($value[self::OBJECT_TYPE_KEY])) {
                        $tmpValue .= ' (?)';
                    }

                    $results[$key] = $tmpValue;
                } elseif (!empty($value[self::VALUE_KEY])) {
                    // We have a simple value, so we use that directly
                    $results[$key] = $value[self::VALUE_KEY];
                } elseif (!empty($value[self::EXPRESSION_KEY])) {
                    // We have an eel expression, so we wrap it in curly braces to point that out
                    $results[$key] = '${' . $value[self::EXPRESSION_KEY] . '}';
                } else {
                    // We seem to have a nested array, so we flatten that recursively
                    $results[$key] = $this->flattenPrototypeDefinition($value);
                }

                continue;
            }

            if (!empty($value[self::OBJECT_TYPE_KEY])) {
                // We have a nested prototype so we strip the empty internal properties
                // and change it's key to contain the prototype name
                $objectType = $value[self::OBJECT_TYPE_KEY];
                $key = $key . ' [' . $objectType . ']';

                if (!$this->isPrototypeKnown($objectType)) {
                    $key .= ' (?)';
                }

                unset($value[self::EXPRESSION_KEY]);
                unset($value[self::OBJECT_TYPE_KEY]);
                unset($value[self::VALUE_KEY]);
            } elseif (!empty($value[self::VALUE_KEY])) {
                // We have a simple value so we just strip the empty internal properties
                unset($value[self::EXPRESSION_KEY]);
                unset($value[self::OBJECT_TYPE_KEY]);
            } elseif (!empty($value[self::EXPRESSION_KEY])) {
                // We have an eel expression so we just strip the empty internal properties
                unset($value[self::VALUE_KEY]);
                unset($value[self::OBJECT_TYPE_KEY]);
            }

            // As there are additional properties present we need to recursively make sure these get flattened too
            $results[$key] = $this->flattenPrototypeDefinition($value);
        }

        // Remove remaining empty elements recursively
        Arrays::removeEmptyElementsRecursively($results);

        // Remove the prototype object name property as it's usage is primarily internal
        unset($results[self::PROTOTYPE_OBJECT_NAME_KEY]);

        return $results;
    }
}
