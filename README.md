# MCStreetguy.FusionDebugger

A small plugin for the awesome Neos CMS, to improve debugging of Fusion DSL code.

**PLEASE NOTE! This plugin is currently work in progress and alls of it's parts are subject to change probably! You should not rely on this in a production environment yet as it is not proven to be stable!**

-------
## Table of Contents

- [MCStreetguy.FusionDebugger](#mcstreetguyfusiondebugger)
  - [Table of Contents](#table-of-contents)
  - [Overview](#overview)
  - [Installation](#installation)
    - [Troubleshooting](#troubleshooting)
      - [`Could not find a version of package mcstreetguy/fusion-debugger matching your minimum-stability`](#could-not-find-a-version-of-package-mcstreetguyfusion-debugger-matching-your-minimum-stability)
      - [`Invalid controller class name "". Make sure your controller is in a folder named "Command" and it's name ends in "CommandController"`](#invalid-controller-class-name--make-sure-your-controller-is-in-a-folder-named-command-and-its-name-ends-in-commandcontroller)
    - [Support for older Neos / PHP](#support-for-older-neos--php)
  - [Reference](#reference)
    - [Commands](#commands)
      - [`fusion:debugprototype`](#fusiondebugprototype)
      - [`fusion:showobjecttree`](#fusionshowobjecttree)
      - [`fusion:lint`](#fusionlint)
      - [`fusion:listprototypes`](#fusionlistprototypes)
    - [Configuration](#configuration)
      - [`fusionFilePathPatterns`](#fusionfilepathpatterns)
      - [`namespaceMap`](#namespacemap)
        - [Namespace Map Example](#namespace-map-example)
  - [Versioning](#versioning)
  - [Authors](#authors)
  - [License](#license)

## Overview

**What is this plugin capable of?**

- Debugging fully merged Fusion prototype definitions
- Visualizing (parts of) the combined Fusion object tree
- Linting Fusion files for syntax errors

**Why do I need it?**

Did you ever came across some really strange rending issue while you created a page in Neos?
And did you ever told yourself: "Why the actual hell is this happening? It's supposed to do something different."  
Well then you probably know about the reasons behind this plugin, it's _improved debugging_.

It's currently not possible in common Neos installations to have a closer look behind the scenes of Fusion rendering.
It parses some code, does some magic and then hopefully your expected result will appear.
But what happens actually in between?  
To get rid of that uncertainty this Plugin allows you to visualize what you normally won't see: the merged Fusion prototype configuration and the combined object tree.

## Installation

Install the plugin by requiring it through composer:

``` bash
composer require --dev mcstreetguy/fusion-debugger
```

### Troubleshooting

#### `Could not find a version of package mcstreetguy/fusion-debugger matching your minimum-stability`

Please make sure that your `minimum-stability` is at least set to `alpha` as this package has no stable release yet.
Alternatively you could require the package with an explicit alpha-version constraint, but please note that this will only work for root-level manifests:

``` bash
composer require --dev mcstreetguy/fusion-debugger:@alpha
```

#### `Invalid controller class name "". Make sure your controller is in a folder named "Command" and it's name ends in "CommandController"`

This error happens only straight after installing the plugin as this corrupts the internal code caches of Flow in some way.  
We couldn't locate the origin of this problem yet but hope to resolve it asap!
Until then we recommend force-clearing the application caches after requiring the plugin through composer, as the automatic graceful clear during the installation is what probably causes this problem to arise.

``` bash
/path/to/flow flow:cache:flush --force
```

In some edge cases it might happen that the entire Flow CLI stops working upon installation.
In that case you are required to manually empty the corresponding `Data/Temporary` directory, that will get Flow running again.
If you are uncertain which directories to remove, you may also delete the entire directory at once, causing Flow to regenerate it fully.

If you encounter this error on a production server you also may need to flush the caches for 'Production'-context.  

``` bash
FLOW_CONTEXT=Production /path/to/flow flow:cache:flush --force
```

**However, you should not install debugger tools in a production environment anyway!**

### Support for older Neos / PHP

The plugin normally is written vor Neos v3.3 and above and in addition makes extensive use of PHP7.1 features.
To make the functionality available for Neos v3.0-v3.2 and also for older PHP versions like 7.0 and 5.6 the plugin has been backported.
In that version all type hinting has been replaced by assertions and the composer requirements have been adapted.

To install the backported version you may also require it through composer:

``` bash
composer require --dev mcstreetguy/fusion-debugger:dev-backport
```

Please not that this backported version will not be tagged accordingly.
The backport branch will be kept up with the master as much as possible to provide you latest features, but we cannot guarantee that this will happen simultaneously.

## Reference

### Commands

The plugin provides several commands to the Flow CLI. These are listed below for reference.
Please see the respective help pages for more detailled information.

#### `fusion:debugprototype`
  
> `mcstreetguy.fusiondebugger:fusion:debugprototype [--no-color] [--not-flat] <prototype>`

Reads the definition of the requested prototype from the `__prototypes` key in the parsed object tree and resolves the contained prototype chain very carefully so that the result contains all properties, either inherited or explictely defined.
For better readability, this command also includes something similar to syntax highlighting as several parts of the built tree are colored (such as eel expressions, further prototype names or just plain strings). Furthermore it flattens the resulting data by removing empty properties and combining the internal properties for e.g. plain values (as these are stored with three properties but could be displayed directly without an array structure).
These additional behaviour can be suppressed by specifying the options `--no-color` or `--not-flat` if it corrupts the resulting data or your terminal does not support ANSI colors.
If you have namespace mappings defined in `MCStreetguy.FusionDebugger.namespaceMap`, these will be resolved before loading the prototype.

#### `fusion:showobjecttree`

> `mcstreetguy.fusiondebugger:fusion:showobjecttree [--path <path>]`

Builds the object tree from all Fusion files and displays it in an ASCII tree structure (excluding the `__prototypes` key as we got the above command for that).
You can optionally provide a dot-separated path that will be loaded instead of the whole tree. (e.g. `./flow fusion:showobjecttree --path root.page`)

#### `fusion:lint`

> `mcstreetguy.fusiondebugger:fusion:lint [--package-key <packageKey>] [--verbose] [--quiet]`

Checks all Fusion files individually for syntax errors and lists the incorrect files with their associated package and file path.
This command was intended to programmatically check the correctness of the Fusion source code and is in fact still an experiment but listed for the sake of completeness.

#### `fusion:listprototypes`

> `mcstreetguy.fusiondebugger:fusion:listprototypes [--no-format]`

Lists all known Fusion prototype names.  
If the `--no-format` option is specified, the list will lack any bullets and return unsorted.

### Configuration

The plugin comes with minimal configuration options available. These are listed below for reference.

#### `fusionFilePathPatterns`

An array of file path patterns used to search for Fusion files that will be loaded.  
The default path `resource://@package/Private/Fusion/` is already present for ease of use.
If your setup involves Fusion files at other locations as the default one provide these here.

The following placeholders can be used inside the pattern and will be exchanged with real values upon evaluation:

| **Placeholder** | **Description** |
|----------------:|:----------------|
| `@package` | The current package key from where the fusion gets loaded. |

#### `namespaceMap`

An associative array of fusion namespace shorthands to full namespaces mappings.
By default, no namespace is set as this varies widely and depends on the current use case.
If you define namespace mappings here, these will be taken into account for the [`fusion:debugprototype`](#fusiondebugprototype) command.

##### Namespace Map Example

```yaml
MCStreetguy:
  FusionDebugger:
    namespaceMap:
      'N': 'Neos.Neos'
      'F': 'Neos.Fusion'
```

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/MCStreetguy/fusion-debugger/tags). 

## Authors

* **Maximilian Schmidt** - _Owner_ - [MCStreetguy](https://github.com/MCStreetguy/)

See also the list of [contributors](https://github.com/MCStreetguy/fusion-debugger/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
