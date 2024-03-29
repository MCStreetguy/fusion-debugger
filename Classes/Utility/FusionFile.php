<?php
namespace MCStreetguy\FusionDebugger\Utility;

/*
 * This file is part of the MCStreetguy.FusionDebugger package.
 */

use MCStreetguy\FusionDebugger\Exceptions\FusionFileException;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\FusionSourceCode;

/**
 * An immutable fusion file representation, containing metadata and tunneling access to file contents.
 *
 * @Flow\Scope("prototype")
 * @internal
 */
class FusionFile
{
    /**
     * The belonging package key of the corresponding fusion file.
     * @var string
     */
    protected $packageKey;

    /**
     * The full path to the corresponding fusion file.
     * @var string
     */
    protected $fullPath;

    /**
     * The parent directory of the corresponding fusion file.
     * @var string
     */
    protected $basedir;

    /**
     * The sole filename of the corresponding fusion file.
     * @var string
     */
    protected $filename;

    /**
     * The last access time of the corresponding fusion file.
     * @var int
     */
    protected $lastAccess;

    /**
     * The last modification time of the corresponding fusion file.
     * @var int
     */
    protected $lastModification;

    /**
     * The filesize of the corresponding fusion file.
     * @var int
     */
    protected $size;

    /**
     * The contents of the corresponding fusion file.
     * @var string
     */
    protected $contents = null;

    /**
     * Construct a new instance.
     *
     * @param string $packageKey The origin package key of the corresponding fusion file.
     * @param string $filePathAndName The full path to the corresponding fusion file.
     * @throws FusionFileException If $filePathAndName does not exist or is not readable.
     */
    public function __construct(string $packageKey, string $filePathAndName)
    {
        if (!file_exists($filePathAndName) || !is_readable($filePathAndName)) {
            throw FusionFileException::forMissingOrNotReadable($filePathAndName);
        }

        $this->packageKey = $packageKey;
        $this->fullPath = $filePathAndName;
        $this->size = filesize($filePathAndName);
        $this->basedir = dirname($filePathAndName);
        $this->filename = basename($filePathAndName);
        $this->lastAccess = fileatime($filePathAndName);
        $this->lastModification = filemtime($filePathAndName);
    }

    /**
     * Get the belonging package key of the corresponding fusion file.
     *
     * @return string
     */
    public function getPackageKey()
    {
        return $this->packageKey;
    }

    /**
     * Get the full path to the corresponding fusion file.
     *
     * @return string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * Get the parent directory of the corresponding fusion file.
     *
     * @return string
     */
    public function getBasedir()
    {
        return $this->basedir;
    }

    /**
     * Get the sole filename of the corresponding fusion file.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Get the last access time of the corresponding fusion file.
     *
     * @return int
     */
    public function getLastAccess()
    {
        return $this->lastAccess;
    }

    /**
     * Get the last modification time of the corresponding fusion file.
     *
     * @return int
     */
    public function getLastModification()
    {
        return $this->lastModification;
    }

    /**
     * Get the filesize of the corresponding fusion file.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the contents of the corresponding fusion file.
     *
     * @return string
     */
    public function getContents()
    {
        if ($this->contents === null) {
            $this->contents = file_get_contents($this->fullPath);
        }

        return $this->contents;
    }

    /**
     * Get the corresponding `FusionSourceCode` object for this file.
     *
     * @return FusionSourceCode
     */
    public function getFusionSourceCode()
    {
        return FusionSourceCode::fromFilePath($this->fullPath);
    }
}
