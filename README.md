# MCStreetguy.FusionDebugger

A small plugin for the awesome Neos CMS, to improve debugging of Fusion DSL code.

-------

**PLEASE NOTE! This plugin is currently work in progress and alls of it's parts are subject to change probably! You should not rely on this in a production environment yet as it is not proven to be stable!**

-------

- [MCStreetguy.FusionDebugger](#mcstreetguyfusiondebugger)
  - [Overview](#overview)
  - [Installation](#installation)
    - [Troubleshooting](#troubleshooting)
  - [Usage](#usage)
  - [Reference](#reference)
    - [Commands](#commands)
      - [`fusion:debugprototype`](#fusiondebugprototype)
      - [`fusion:showobjecttree`](#fusionshowobjecttree)
      - [`fusion:lint`](#fusionlint)
    - [Configuration](#configuration)
      - [`fusionFilePathPatterns`](#fusionfilepathpatterns)
  - [Contributing](#contributing)
  - [Versioning](#versioning)
  - [Authors](#authors)
  - [License](#license)

## Overview

**What is this plugin capable of?**

- Linting fusion files for syntax errors
- Debugging fully merged prototype definitions
- Visualizing (parts of) the combined object tree

**Why do I need it?**

Short answer: You don't necessarily.

**But then why should I want to have it?**

Did you ever came across some really strange rending issue while you created a page in Neos?
And did you ever told yourself: "Why the actual fuck is this happening? It's supposed to do something different."  
Well then you probably know about the reasons behind this plugin, it's _improved debugging_.

It's currently not possible in common Neos installations to have a closer look behind the scenes of Fusion rendering. It parses some code, does some magic and then hopefully your expected result will appear.
But what happens actually in between?  
To get rid of that uncertainty this Plugin allows you to visualize what you normally won't see: the merged Fusion prototype configuration and the combined object tree.

## Installation

Install the plugin by requiring it through composer:

``` bash
composer require mcstreetguy/fusion-debugger
```

### Troubleshooting

> Could not find a version of package mcstreetguy/fusion-debugger matching your minimum-stability

Please make sure that your `minimum-stability` is at least set to `alpha` as this package has no stable release yet.
Alternatively you could require the package with an explicit version constraint, but please note that this only works for root-level manifests:

``` bash
composer require mcstreetguy/fusion-debugger:0.2-alpha
```

## Usage

_to be written_

## Reference

### Commands

The plugin provides several commands to the Flow CLI.
Each command has a detailled help text available to guide you through it's usage.
These are listed below for reference.
Please see the respective help pages for more information.

#### `fusion:debugprototype`
  
> `mcstreetguy.fusiondebugger:fusion:debugprototype [--no-color] [--not-flat] <prototype>`

Reads the definition of the requested prototype from the `__prototypes` key in the parsed object tree and resolves the contained prototype chain very carefully so that the result contains all properties, either inherited or explictely defined.
For better readability, this command also includes something similar to syntax highlighting as several parts of the built tree are colored (such as eel expressions, further prototype names or just plain strings). Furthermore it flattens the resulting data by removing empty properties and combining the internal properties for e.g. plain values (as these are stored with three properties but could be displayed directly without an array structure).
These additional behaviour can be suppressed by specifying the options `--no-color` or `--not-flat` if it corrupts the resulting data or your terminal does not support ANSI colors.

#### `fusion:showobjecttree`

> `mcstreetguy.fusiondebugger:fusion:showobjecttree [--path <path>]`

Builds the object tree from all Fusion files and displays it in an ASCII tree structure (excluding the `__prototypes` key as we got the above command for that).

#### `fusion:lint`

> `mcstreetguy.fusiondebugger:fusion:lint [--package-key <packageKey>] [--verbose]`

Checks all Fusion files individually for syntax errors and lists the incorrect files with their associated package and file path.
This command was intended to programmatically check the correctness of the Fusion source code and is in fact still an experiment but listed for the sake of completeness.

### Configuration

The plugin comes with minimal configuration options available.
These are listed below for reference.
All options are lacking the package name prefix, prepend them with `MCStreetguy.FusionDebugger.` when you want to modify them.

#### `fusionFilePathPatterns`

An array of file path patterns used to search for Fusion files that will be loaded.  
The default path `resource://@package/Private/Fusion/` is already present for ease of use.
If your setup involves Fusion files at other locations as the default one provide these here.

The following placeholders can be used inside the pattern and will be exchanged with real values upon evaluation:

| **Placeholder** | **Description** |
|-----------------|------------------------------------------------------------|
| `@package` | The current package key from where the fusion gets loaded. |

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/MCStreetguy/fusion-debugger/tags). 

## Authors

* **Maximilian Schmidt** - _Owner_ - [MCStreetguy](https://github.com/MCStreetguy/)

See also the list of [contributors](https://github.com/MCStreetguy/fusion-debugger/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
