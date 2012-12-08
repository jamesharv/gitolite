<?php
/**
 * @file
 *  Gitolite Entity
 */
namespace Gitolite\Entity;
use Git\Repository, Gitolite\File;

abstract class Entity implements Groupable
{
    protected $entityType;
    protected $name;
    protected $files;

    /**
     * Constructs an Entity.
     *
     * @param string $type The type of entity.
     * @param string $name The name of the entity.
     * @param array $files The files which represent this entity.
     */
    public function __construct($type, $name, array $files = array())
    {
        $this->setEntityType($type);
        $this->setName($name);
        $this->setFiles($files);
        $this->load();
    }

    /**
     * An id for this entity.
     *
     * @return string This entity's id.
     */
    public function getId()
    {
        return $this->getName();
    }

    /**
     * Sets the type of this entity.
     *
     * @param string $type The entity type.
     */
    public function setEntityType($type)
    {
        $this->entityType = ucfirst(strtolower($type));
    }

    /**
     * Gets the type of this entity.
     *
     * @return string The entity type.
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * Sets the name of this entity.
     *
     * @param string $name The entity name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the name of this entity.
     *
     * @return string The entity name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a file to the Entity.
     *
     * @param Gitolite\File $file The file to add.
     */
    public function addFile(File $file)
    {
        $this->files[$file->getFilename()] = $file;
    }

    /**
     * Adds a file to the Entity.
     *
     * @param Gitolite\File $file The file to add.
     */
    public function removeFile(File $file)
    {
        unset($this->files[$file->getFilename()]);
    }

    /**
     * Sets the files that represent this entity.
     *
     * @param array $files The files that represent this entity.
     */
    public function setFiles(array $files)
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }
    }

    /**
     * Returns all the files associated with this Entity.
     *
     * @return array of Gitolite\File objects.
     */
    public function getFiles()
    {
        return $this->files ?: array();
    }

    /**
     * Returns the file with the given name.
     *
     * @param string The name of the file to return.
     * @return Gitolite\File The file with the given name or NULL if there is no file with that name.
     */
    public function getFile($name)
    {
        $files = $this->getFiles();
        return isset($files[$name]) ? $files[$name] : NULL;
    }

    /**
     * Returns the label of this entity for use in group config.
     *
     * @see Gitolite\Groupable
     */
    public function getGroupableLabel()
    {
        return $this->name;
    }

    public abstract function load();
}