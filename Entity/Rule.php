<?php
/**
 * @file
 *  Gitolite Group
 */
namespace Gitolite\Entity;
use Gitolite\File;

class Rule
{
    protected $permission;
    protected $refexes;
    protected $entity;

    /**
     * Constructs a new group.
     *
     * @param string $name The group's name.
     * @param array $items The items in the group.
     */
    public function __construct($permission, array $refexes, Groupable $entity)
    {
        $this->setPermission($permission);
        $this->setRefexes($refexes);
        $this->setEntity($entity);
    }

    /**
     * Sets the rule's permission.
     *
     * @param string $permission The permission (eg. R or RW etc.)
     * @see http://sitaramc.github.com/gitolite/rules.html
     */
    public function setPermission($permission)
    {
        $this->permission = trim($permission);
    }

    /**
     * Returns this rule's permission.
     *
     * @return string The rule's permission.
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * Sets this rule's refexes.
     *
     * @param array This rule's refexes.
     */
    public function setRefexes(array $refexes)
    {
        $this->refexes = array();
        foreach ($refexes as $refex) {
            $this->addRefex($refex);
        }
    }

    /**
     * Adds a new refex to this rule.
     *
     * @param string The refex to add.
     */
    public function addRefex($refex)
    {
        $this->refexes[$refex] = $refex;
    }

    /**
     * Returns this rule's refexes.
     *
     * @return array This rule's refexes.
     */
    public function getRefexes()
    {
        return $this->refexes;
    }

    /**
     * Sets the entity this rule applies to.
     *
     * @param Gitolite\Entity\Groupable $entity The entity the rule applies to.
     */
    public function setEntity(Groupable $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Returns the entity this rule applies to.
     *
     * @return Gitolite\Entity\Groupable The entity this rule applies to.
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function __toString()
    {
        $refexes = implode(' ', $this->getRefexes());
        $permission = $this->getPermission();
        $entity = $this->getEntity()->getGroupableLabel();
        return "\t$permission $refexes = $entity";
    }
}
