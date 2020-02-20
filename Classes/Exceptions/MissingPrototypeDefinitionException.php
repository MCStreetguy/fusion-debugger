<?php

namespace MCStreetguy\FusionDebugger\Exceptions;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use Webmozart\Assert\Assert;

/**
 * An exception for a missing prototype definition.
 */
class MissingPrototypeDefinitionException extends AbstractDebuggerException
{
    public static function forPrototypeName($name)
    {
        Assert::string($name);
        return new self("Could not find any prototype definition for {$name}! " . "Maybe you misspelled the name or it's source package is not active.", 1564131267910);
    }
}
