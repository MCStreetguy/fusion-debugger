<?php
namespace MCStreetguy\FusionDebugger\Exceptions;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use Neos\Flow\Exception;

/**
 * An abstract exception type, thrown by the debugger.
 * This is used to easily identify package-internal exceptions.
 */
abstract class AbstractDebuggerException extends Exception
{
}
