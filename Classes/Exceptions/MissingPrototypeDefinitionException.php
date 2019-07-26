<?php
namespace MCStreetguy\FusionDebugger\Exceptions;

use Neos\Flow\Exception;

class MissingPrototypeDefinitionException extends Exception
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
