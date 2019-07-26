<?php
namespace MCStreetguy\FusionDebugger\Fusion;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use MCStreetguy\FusionDebugger\Exceptions\FusionParseErrorException;
use MCStreetguy\FusionDebugger\Exceptions\MissingPrototypeDefinitionException;
use MCStreetguy\FusionDebugger\Fusion\Utility\Files;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Parser;
use Neos\Utility\Arrays;

/**
 * @Flow\Scope("singleton")
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
    protected $fusionTree = [];

    /**
     *
     */
    public function isPrototypeKnown(string $name)
    {
        return array_key_exists($name, $this->loadFusionTree()[self::PROTOTYPES_KEY]);
    }

    /**
     *
     */
    public function loadPrototype(string $name)
    {
        $prototypes = $this->loadFusionTree()[self::PROTOTYPES_KEY];

        if (!array_key_exists($name, $prototypes)) {
            throw MissingPrototypeDefinitionException::forPrototypeName($name);
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

        $prototypeChain = $bareDefinition[self::PROTOTYPE_CHAIN_KEY];
        $rootPrototype = array_shift($prototypeChain);
        $definition = $this->loadPrototype($rootPrototype);

        if (count($prototypeChain) > 0) {
            foreach ($prototypeChain as $prototype) {
                $chainedDefinition = $this->loadPrototype($prototype);
                $definition = $this->mergeFusionDefinitions($definition, $chainedDefinition);
            }
        }

        $definition = $this->mergeFusionDefinitions($definition, $bareDefinition);

        unset($definition[self::PROTOTYPE_CHAIN_KEY]);
        Arrays::sortKeysRecursively($definition, \SORT_NATURAL);

        return $definition;
    }

    protected function mergeFusionDefinitions(array $baseDefinition, array $extenderDefinition)
    {
        foreach ($extenderDefinition as $key => $value) {
            if (is_object($value)) {
                // Should not happen normally but just in case that we receive an object we convert it to an array
                $value = Arrays::convertObjectToArray($value);
            }

            if (!array_key_exists($key, $baseDefinition) || !is_array($value)) {
                // Key does not exist in definition or the value is a simple type, thus is set directly
                $baseDefinition[$key] = $value;
            } elseif (array_key_exists(self::OBJECT_TYPE_KEY, $value)) {
                // Key is present in definition but replaces previous value, thus is overridden
                $baseDefinition[$key] = $value;
            } else {
                // Key is present in definition and extends previous value, thus gets merged properly
                $baseDefinition[$key] = $this->mergeFusionDefinitions($baseDefinition[$key], $value);
            }
        }

        return $baseDefinition;
    }

    public function flattenFusionDefinition(array $definition, callable $callback)
    {
        $results = [];

        foreach ($definition as $key => $value) {
            // \Kint::dump(
            //     $key,
            //     $value,
            //     is_object($value),
            //     is_array($value),
            //     @count($value) === 3,
            //     @array_key_exists(self::OBJECT_TYPE_KEY, $value),
            //     @array_key_exists(self::EXPRESSION_KEY, $value),
            //     @array_key_exists(self::VALUE_KEY, $value),
            //     empty($value[self::OBJECT_TYPE_KEY]),
            //     empty($value[self::VALUE_KEY]),
            //     empty($value[self::EXPRESSION_KEY])
            // );
            // call_user_func($callback);
            // continue;
            // #############################

            if (is_object($value)) {
                $value = Arrays::convertObjectToArray($value);
            } elseif (!is_array($value)) {
                $results[$key] = $value;
                continue;
            }

            if ((
                count($value) === 3 &&
                array_key_exists(self::OBJECT_TYPE_KEY, $value) &&
                array_key_exists(self::EXPRESSION_KEY, $value) &&
                array_key_exists(self::VALUE_KEY, $value)
            )) {
                if (!empty($value[self::OBJECT_TYPE_KEY])) {
                    $tmpKey = $key . ' [' . $value[self::OBJECT_TYPE_KEY] . ']';

                    if (!$this->isPrototypeKnown($value[self::OBJECT_TYPE_KEY])) {
                        $tmpKey .= ' (?)';
                    }

                    $results[$key] = 'prototype(' . $value[self::OBJECT_TYPE_KEY] . ')';
                } elseif (!empty($value[self::VALUE_KEY])) {
                    $results[$key] = $value[self::VALUE_KEY];
                } elseif (!empty($value[self::EXPRESSION_KEY])) {
                    $results[$key] = '${' . $value[self::EXPRESSION_KEY] . '}';
                } else {
                    $results[$key] = $this->flattenFusionDefinition($value, $callback);
                }

                continue;
            }

            if (!empty($value[self::OBJECT_TYPE_KEY])) {
                $objectType = $value[self::OBJECT_TYPE_KEY];
                $tmpKey = $key . ' [' . $objectType . ']';

                if (!$this->isPrototypeKnown($objectType)) {
                    $tmpKey .= ' (?)';
                }

                $results[$tmpKey] = $value;
                unset($results[$tmpKey][self::EXPRESSION_KEY]);
                unset($results[$tmpKey][self::OBJECT_TYPE_KEY]);
                unset($results[$tmpKey][self::VALUE_KEY]);
            } elseif (!empty($value[self::VALUE_KEY])) {
                unset($value[self::EXPRESSION_KEY]);
                unset($value[self::OBJECT_TYPE_KEY]);

                $results[$key] = $value;
            } elseif (!empty($value[self::EXPRESSION_KEY])) {
                unset($value[self::VALUE_KEY]);
                unset($value[self::OBJECT_TYPE_KEY]);

                $results[$key] = '${' . $value[self::EXPRESSION_KEY] . '}';
            } else {
                $results[$key] = $this->flattenFusionDefinition($value, $callback);
            }
        }

        return $results;
    }
}