<?php
namespace MCStreetguy\FusionDebugger\Exceptions;

use Neos\Flow\Exception;

/**
 * A fusion file exception.
 */
class FusionFileException extends Exception
{
    /**
     * Create an exception object for a missing or not readable fusion file.
     * @return self
     */
    public static function forMissingOrNotReadable(string $filePathAndName)
    {
        return new self("Fusion file $filePathAndName does not exist or is not readable!", 1564159029025);
    }
}
