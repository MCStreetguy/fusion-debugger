# MCStreetguy.FusionDebugger

A small plugin for the awesome Neos CMS, to improve debugging of Fusion DSL code.

-------

- [Overview](#overview)
- [Installation](#installation)
  - [Usage outside of Neos](#usage-outside-of-neos)
  - [Troubleshooting](#troubleshooting)
    - [`Invalid controller class name "". Make sure your controller is in a folder named "Command" and it's name ends in "CommandController"`](#invalid-controller-class-name--make-sure-your-controller-is-in-a-folder-named-command-and-its-name-ends-in-commandcontroller)
- [Reference](#reference)
  - [Commands](#commands)
    - [`fusion:debugprototype`](#fusiondebugprototype)
    - [`fusion:showobjecttree`](#fusionshowobjecttree)
    - [`fusion:lint`](#fusionlint)
    - [`fusion:listprototypes`](#fusionlistprototypes)
  - [Configuration](#configuration)
    - [`fusionFilePathPatterns`](#fusionfilepathpatterns)
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

If composer refuses to install the plugin, try requiring a specific version of it.
The major version of this project will always work with the corresponding Neos release.

``` bash
composer require --dev mcstreetguy/fusion-debugger:^8.0   # for Neos v8.x
composer require --dev mcstreetguy/fusion-debugger:^7.0   # for Neos v7.x
composer require --dev mcstreetguy/fusion-debugger:^5.0   # for Neos v5.x
composer require --dev mcstreetguy/fusion-debugger:^4.0   # for Neos v4.x
composer require --dev mcstreetguy/fusion-debugger:^3.0   # for Neos v3.x
```

We support all Neos versions ranging from 3.0 up to 7.0 officially.
For any other version you may encounter unexpected issues and there is no guarantee that the debugger will work properly!
(Even though we kind of support Neos v2.3, see [below](#support-for-older-neos--php) for more information.)

### Usage outside of Neos

As the plugin only relies on `neos/flow` and `neos/fusion` as dependencies, you can actually use it outside of Neos projects as long as both required components are available.
Testing this is currently an ongoing process, but any feedback on compatibility outside Neos is greatly appreciated.
If you come across any issues not covered in the [troubleshooting section below](#troubleshooting), please [report them](https://github.com/MCStreetguy/fusion-debugger/issues).

### Troubleshooting

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

## Reference

### Commands

The plugin provides several commands to the Flow CLI. These are listed below for reference.
Please see the respective help pages for more detailled information.

#### `fusion:debugprototype`
  
> `mcstreetguy.fusiondebugger:fusion:debugprototype [--no-color] [--no-flatten] <prototype>`

Reads the definition of the requested prototype from the `__prototypes` key in the parsed object tree and resolves the contained prototype chain very carefully so that the result contains all properties, either inherited or explictely defined.
For better readability, this command also includes something similar to syntax highlighting as several parts of the built tree are colored (such as eel expressions, further prototype names or just plain strings). Furthermore it flattens the resulting data by removing empty properties and combining the internal properties for e.g. plain values (as these are stored with three properties but could be displayed directly without an array structure).
These additional behaviour can be suppressed by specifying the options `--no-color` or `--no-flatten` if it corrupts the resulting data or your terminal does not support ANSI colors.

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

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/MCStreetguy/fusion-debugger/tags).  

## Authors

* **Maximilian Schmidt** - _Owner_ - [MCStreetguy](https://github.com/MCStreetguy/)

See also the list of [contributors](https://github.com/MCStreetguy/fusion-debugger/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
