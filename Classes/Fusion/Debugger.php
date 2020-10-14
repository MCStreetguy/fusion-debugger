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
use Neos\Utility\PositionalArraySorter;

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
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $settings;

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
     * @return array The requested prototype definition
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

    /**
     * Load all prototype definitions.
     *
     * @param bool $returnBare Return the plain definitions and don't merge anything
     * @return array All available prototype definitions
     */
    public function loadAllDefinitions(bool $returnBare = false)
    {
        $prototypes = $this->loadFusionTree()[self::PROTOTYPES_KEY];

        if ($returnBare === false) {
            foreach ($prototypes as $key => &$value) {
                $value = $this->mergePrototypeChain($key, $value);
            }
        }

        return $prototypes;
    }

    /**
     * Get the fusion object tree, optionally filtered by a path.
     *
     * @param string $path A path to retrieve from the object tree (in form of "foo.bar.baz")
     * @return array|mixed The loaded object tree or the value found at the given path
     */
    public function getObjectTree(string $path = null)
    {
        $objectTree = $this->loadFusionTree();

        // Remove the prototypes key as we have seperate methods for that
        unset($objectTree[self::PROTOTYPES_KEY]);

        if ($path !== null) {
            return Arrays::getValueByPath($objectTree, $path);
        }

        return $objectTree;
    }

    /**
     * Get all defined prototype names.
     *
     * @return string[]
     */
    public function getPrototypeNames()
    {
        return array_keys($this->loadFusionTree()[self::PROTOTYPES_KEY]);
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
     * Retrieve the prototype chain for the given base type and merge the corresponding definitions together.
     *
     * @param string $basePrototype The base prototype name to resolve
     * @param array $bareDefinition The plain prototype definition of the prototype
     * @return array The resolved definition
     */
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
     * @see https://github.com/neos/neos-development-collection/blob/3.3/Neos.Fusion/Classes/Core/Runtime.php#L141
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
                    $tmpValue = '[' . $value[self::OBJECT_TYPE_KEY] . ']';
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

            if ($key === self::META_INFO_KEY) {
                // We have meta keys so we prepend them with '@' and add them directly to the result
                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        // If the nested value is an array it has to be flattened too
                        $subvalue = $this->flattenPrototypeDefinition($subvalue);
                    }

                    $results['@' . $subkey] = $subvalue;
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

                if (!empty($value[self::META_INFO_KEY])) {
                    // We have nested meta keys so we prepend them with '@' and add them directly to the result
                    foreach ($value[self::META_INFO_KEY] as $subkey => $subvalue) {
                        if (is_array($subvalue)) {
                            // If the nested value is an array it has to be flattened too
                            $subvalue = $this->flattenPrototypeDefinition($subvalue);
                        }

                        $value['@' . $subkey] = $subvalue;
                    }

                    // Remove the actual meta property as it has been fully resolved
                    unset($value[self::META_INFO_KEY]);
                }
            } elseif (!empty($value[self::VALUE_KEY])) {
                // We have a simple value so we strip the internal properties and display it directly
                $key = $key . ' => "' . $value[self::VALUE_KEY] . '"';
                unset($value[self::VALUE_KEY]);
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

        // Sort the data by their positional array property
        $sortedResults = new PositionalArraySorter($results, '@position');
        $results = $sortedResults->toArray();

        // Move all meta keys to the beginning of the array
        $containedMetaKeys = preg_grep('/^@/', array_keys($results));
        if (count($containedMetaKeys) > 0) {
            sort($containedMetaKeys, (SORT_FLAG_CASE));
            foreach (array_reverse($containedMetaKeys, true) as $key) {
                $results = [$key => $results[$key]] + $results;
            }
        }

        return $results;
    }

    /**
     * Print a recursive fusion tree structure with box drawing characters to the terminal.
     * Additionally this returns the tree as array of strings.
     *
     * @param array $data The associative data to display
     * @param string $root The root key to display on top of the tree (defaults to '.')
     * @return array
     */
    public function buildVisualFusionTree(array $data, string $root = '.')
    {
        $tree = [$root];
        $cycle = 0;
        $count = count($data);

        foreach ($data as $key => $value) {
            $prefix = '├── ';

            // Change box-decorator prefix if element is the last child
            if (($isLast = ($cycle === $count - 1)) === true) {
                $prefix = '└── ';
            }

            $type = gettype($value);

            if ($type === 'array') {
                // Render the tree for the nested array and append it to the current
                $isFirst = true;
                $nestedTree = $this->buildVisualFusionTree($value, $key);

                foreach ($nestedTree as $nestedLine) {
                    if ($isFirst === true) {
                        // Don't indent the first line of the tree as we already have proper indentation
                        $tree[] = $prefix . $nestedLine;
                    } elseif ($isLast === true) {
                        // Prepend the nested line with 4 spaces as there is no further parent-sibling
                        $tree[] = '    ' . $nestedLine;
                    } else {
                        // Prepend the nested line with a box-decorator and 3 spaces as there are more parent-siblings to render
                        $tree[] = '│   ' . $nestedLine;
                    }

                    $isFirst = false;
                }
            } elseif ($type === 'object') {
                // Render a static label with the classname of the object
                $tree[] = $prefix . $key . ' => object<' . get_class($value) . '>';
            } elseif ($value === null) {
                // Special treatment for values that are explicitly 'null'
                $tree[] = $prefix . $key . ' => null';
            } elseif ($value === false) {
                // Special treatment for values that are explicitly 'false'
                $tree[] = $prefix . $key . ' => false';
            } elseif ($value === true) {
                // Special treatment for positive boolean values
                $tree[] = $prefix . $key . ' => true';
            } elseif (empty($value)) {
                // Render a placeholder to show that the key is explictly empty
                $tree[] = $prefix . $key . ' => <empty>';
            } elseif ($key === '__eelExpression' && substr($value, 0, 2) !== '${') {
                // Surround eel expressions with '${...}' to make them look like such
                $tree[] = $prefix . $key . ' => ${' . $value . '}';
            } elseif ($type === 'string' && $key !== '__objectType' && substr($value, 0, 2) !== '${' && !preg_match('/\[[a-zA-Z0-9.:]+\]/', $value)) {
                // Sourround strings that are not object names with quotation marks
                $tree[] = $prefix . $key . ' => "' . $value . '"';
            } else {
                // Render the static 'key => value' label for all other cases
                $tree[] = $prefix . $key . ' => ' . $value;
            }

            $cycle++;
        }

        // Return the tree as array of strings for internal further use
        return $tree;
    }
}
