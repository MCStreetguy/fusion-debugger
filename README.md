# MCStreetguy.FusionLinter

A small plugin for the awesome Neos CMS, to improve debugging of Fusion DSL code.

- [MCStreetguy.FusionLinter](#mcstreetguyfusionlinter)
  - [Overview](#overview)
  - [Getting started](#getting-started)
    - [Installation](#installation)
    - [Usage](#usage)
      - [`fusion:lint`](#fusionlint)
      - [`fusion:debug`](#fusiondebug)
      - [`fusion:showobjecttree`](#fusionshowobjecttree)
      - [`fusion:showprototypehierachie`](#fusionshowprototypehierachie)
  - [Contributing](#contributing)
  - [Versioning](#versioning)
  - [Authors](#authors)
  - [License](#license)

## Overview

**What is this plugin capable of?**

- Linting fusion files for syntax errors
- Debugging single prototype definitions
- Debugging the whole prototype hierachie
- Visualizing (parts of) the combined object tree

**Why do I need it?**

Short answer: You don't.

**But then why should I want to have it?**

Did you ever came across some really strange rending issue while you created a page in Neos?
And did you ever told yourself: "Why the actual fuck is this happening? It's supposed to do something different."  
Well then you probably know about the reasons behind this plugin, it's _improved debugging_.

It's currently not possible in common Neos installations to have a closer look behind the scenes of Fusion rendering. It parses some code, does some magic and suddenly there is your page. But happens in between?  
To get rid of that uncertainty this Plugin ultimately allows you to visualize what you normally won't see: the merged Fusion prototype configuration and the combined object tree.

## Getting started

### Installation

Install the plugin by requiring it through composer:

``` bash
composer require mcstreetguy/fusion-linter
```

### Usage

The plugin provides several commands to the Flow CLI.
Each command has a detailled help text available to guide you through it's usage.
These are listed below as reference.

``` plain
PACKAGE "MCSTREETGUY.FUSIONLINTER":
-------------------------------------------------------------------------------
  fusion:lint                              Lint the existing Fusion code.
  fusion:debug                             Debug the existing Fusion code.
  fusion:showobjecttree                    Show the merged fusion object tree.
  fusion:showprototypehierachie            Show the merged fusion prototype
                                           configuration.


```

#### `fusion:lint`

Iterate each fusion file and try to parse it. If any syntax error occurres it'll be reported to the user with the corresponding file path and containing package.

``` plain
COMMAND:
  mcstreetguy.fusionlinter:fusion:lint

USAGE:
  ./flow fusion:lint [<options>]

OPTIONS:
  --package-key        The package to load the Fusion code from.
  --verbose            Produce additional output with additional information.

DESCRIPTION:
  Lint the existing Fusion code.
```

#### `fusion:debug`

Print all the parsed fusion code to the terminal, in loading order.

``` plain
COMMAND:
  mcstreetguy.fusionlinter:fusion:debug

USAGE:
  ./flow fusion:debug

DESCRIPTION:
  Debug the existing Fusion code.
```

#### `fusion:showobjecttree`

Visualize the fusion object tree.

``` plain
COMMAND:
  mcstreetguy.fusionlinter:fusion:showobjecttree

USAGE:
  ./flow fusion:showobjecttree [<options>]

OPTIONS:
  --path               The fusion path to show (defaults to 'root')
  --verbose            Produce more detailled output

DESCRIPTION:
  Show the merged fusion object tree.
```

#### `fusion:showprototypehierachie`

Show the combined prototype hierachie, or the sole definition of the given prototype.

``` plain
COMMAND:
  mcstreetguy.fusionlinter:fusion:showprototypehierachie

USAGE:
  ./flow fusion:showprototypehierachie [<options>]

OPTIONS:
  --prototype          Show information on the specified prototype only
  --verbose            Produce more detailled output

DESCRIPTION:
  Show the merged fusion prototype configuration.
```

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/PurpleBooth/b24679402957c63ec426) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/MCStreetguy/fusion-linter/tags). 

## Authors

* **Maximilian Schmidt** - _Owner_ - [MCStreetguy](https://github.com/MCStreetguy/)

See also the list of [contributors](https://github.com/MCStreetguy/fusion-linter/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
