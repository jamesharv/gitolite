<?php
/**
 * @file
 *  Gitolite File interface
 */
namespace Gitolite;

class File
{
    protected $contents;
    protected $filename;
    protected $deleted;

    public function __construct($filename, $contents = '')
    {
        $this->setFilename($filename);
        $this->setContents($contents);
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setContents($contents)
    {
        $this->contents = $contents;
    }

    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Sets whether this file is deleted.
     *
     * @param bool $deleted Whether the file is deleted.
     */
    public function setDeleted($deleted = TRUE)
    {
        $this->deleted = (bool) $deleted;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }
}
