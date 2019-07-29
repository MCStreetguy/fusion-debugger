<?php
namespace MCStreetguy\FusionDebugger\Exceptions;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

/**
 * A fusion parse exception.
 */
class FusionParseErrorException extends AbstractDebuggerException
{
    public static function forFile(string $path, \Throwable $previous = null)
    {
        return new self("Failed to parse fusion file at $path!", 1564130709438, $previous);
    }
}
