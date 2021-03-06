<?php
namespace MCStreetguy\FusionDebugger\Exceptions;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

/**
 * An exception for an invalid prototype definition.
 */
class InvalidPrototypeDefinitionException extends AbstractDebuggerException
{
    public static function forPrototypeName(string $name)
    {
        return new self("The prototype definition for $name is invalid!", 1564131470406);
    }
}
