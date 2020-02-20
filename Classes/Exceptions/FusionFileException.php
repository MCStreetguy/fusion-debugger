<?php

namespace MCStreetguy\FusionDebugger\Exceptions;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

/**
 * A fusion file exception.
 */
class FusionFileException extends AbstractDebuggerException
{
    /**
     * Create an exception object for a missing or not readable fusion file.
     * @return self
     */
    public static function forMissingOrNotReadable($filePathAndName)
    {
        Assert::string($filePathAndName);
        return new self("Fusion file {$filePathAndName} does not exist or is not readable!", 1564159029025);
    }
}
