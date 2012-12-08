<?php
/**
 * @file
 *  Gitolite Groupable interface
 */
namespace Gitolite\Entity;

class Placeholder implements Groupable
{
    protected $name;

    /**
     * Constructs a Placeholder.
     *
     * @param string $name The Placeholder's name.
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * Sets the Placeholder's name.
     *
     * @param string $name The Placeholder's name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the Placeholder's name
     *
     * @return string The Placeholder's name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the label of this entity for use in group config.
     *
     * @see Groupable
     */
    public function getGroupableLabel()
    {
        return $this->getName();
    }
}
