<?php
namespace MCStreetguy\FusionDebugger\Exceptions;

use Neos\Flow\Exception;

class InvalidPrototypeDefinitionException extends Exception
{
    public static function forPrototypeName(string $name)
    {
        return new self("The prototype definition for $name is invalid!", 1564131470406);
    }
}
