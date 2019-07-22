<?php
namespace MCStreetguy\FusionLinter\Fusion\Utility;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("prototype")
 */
class FusionFile
{
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

    public function __construct(string $filePathAndName)
    {
        // clearstatcache(true, $filePathAndName);
        $this->basedir = dirname($filePathAndName);
        $this->filename = basename($filePathAndName);
        $this->fullPath = $filePathAndName;
        $this->lastAccess = fileatime($filePathAndName);
        $this->lastModification = filemtime($filePathAndName);
        $this->size = filesize($filePathAndName);
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
}
