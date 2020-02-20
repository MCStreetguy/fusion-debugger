<?php

namespace MCStreetguy\FusionDebugger\Exceptions;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use Webmozart\Assert\Assert;

/**
 * A fusion parse exception.
 */
class FusionParseErrorException extends AbstractDebuggerException
{
    public static function forFile($path, \Throwable $previous = null)
    {
        Assert::string($path);
        return new self("Failed to parse fusion file at {$path}!", 1564130709438, $previous);
    }
}
