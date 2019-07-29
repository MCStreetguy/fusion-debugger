<?php
namespace MCStreetguy\FusionDebugger\Exceptions;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

/**
 * An exception for a missing prototype definition.
 */
class MissingPrototypeDefinitionException extends AbstractDebuggerException
{
    public static function forPrototypeName(string $name)
    {
        return new self(
            "Could not find any prototype definition for $name! " .
            "Maybe you misspelled the name or it's source package is not active.",
            1564131267910
        );
    }
}
