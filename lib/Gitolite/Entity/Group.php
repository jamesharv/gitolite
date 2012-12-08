<?php
/**
 * @file
 *  Gitolite Group
 */
namespace Gitolite\Entity;
use Gitolite\File;

class Group extends Entity
{
    protected $type;
    protected $items;

    /**
     * Constructs a new group.
     *
     * @param string $name The group's name.
     * @param array $items The items in the group.
     */
    public function __construct($name, $type, $file = NULL)
    {
        $files = array();
        if ($file instanceof File) {
            $files[] = $file;
        }
        $this->items = array();
        $this->setType($type);
        parent::__construct('Group', $name, $files);
    }

    /**
     * Returns this group's id.
     *
     * @return string This group's id (type:name)
     */
    public function getId()
    {
        return $this->getType() . ':' . $this->getName();
    }

    /**
     * Returns the label of this entity for use in group config.
     *
     * @see Gitolite\Groupable
     */
    public function getGroupableLabel()
    {
        return '@' . $this->getName();
    }

    /**
     * Sets the type of this Group.
     *
     * @param string $type The type of group (User|Repository).
     * @throws RuntimeException if group type is not User | Repository.
     */
    public function setType($type)
    {
        $type = ucfirst(strtolower($type));
        if (!in_array($type, array('User', 'Repository'))) {
            throw new \RuntimeException("Invalid group type: $type");
        }
        $this->type = $type;
    }

    /**
     * Returns the type of this group.
     *
     * @return string The type of group (User|Repository)
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Adds an item to the group.
     *
     * @param Gitolite\Entity\Groupable $item The item to add to the group.
     */
    public function addItem(Groupable $item)
    {
        $label = $item->getGroupableLabel();
        $this->items[$label] = $item;
    }

    /**
     * Removes an item from the group.
     *
     * @param string|Gitolite\Entity\Groupable $item The item to remove.
     */
    public function removeItem($item)
    {
        $label = $item;
        if ($item instanceof Groupable) {
            $label = $item->getGroupableLabel();
        }
        if (isset($this->items[$label])) {
            unset($this->items[$label]);
        }
        return TRUE;
    }

    /**
     * Returns the items in this group.
     *
     * @return array of the items in the group.
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Loads all items.
     * ????? Might not be needed ?????
     */
    public function loadItems()
    {
        /*foreach ($this->items as $label => $item) {
            if ($item instanceof Placeholder) {
                $this->items[$label] = call_user_func(array($this->getType(), 'load'), $this->getClient(), $label);
            }
        }*/
    }

    /**
     * Loads the group from its config file.
     */
    public function load()
    {
        if (empty($this->files[$this->filename()])) {
            return TRUE;
        }
        $file = $this->files[$this->filename()];
        $config = explode("\n", trim($file->getContents()));
        $format = '@' . $this->getName() . ' = %s';
        foreach ($config as $line) {
            list($label) = sscanf($line, $format);
            if (!empty($label)) {
                $this->addItem(new Placeholder($label));
            }
        }
    }

    /**
     * Update the files associated with this group and return them.
     *
     * @return array of Gitolite\File objects
     */
    public function getFiles()
    {
        $config = '';
        $groupLabel = $this->getGroupableLabel();
        foreach ($this->getItems() as $item) {
            $config .=  "$groupLabel = " . $item->getGroupableLabel() . "\n";
        }

        $filename = $this->filename();
        $file = new File($filename, $config);
        $this->files = array($filename => $file);
        return parent::getFiles();
    }

    /**
     * Gets the filename of this group conf file.
     *
     * @return string The group's filename.
     */
    public function filename()
    {
        return self::formatFilename($this->getName(), $this->getType());
    }

    /**
     * Formats a filename for a group given the type and name.
     *
     * @param string $name The name of the groups.
     * @param string $type The type of group.
     */
    public static function formatFilename($name, $type)
    {
        return strtolower(sprintf('%s.%s.conf', $type, $name));
    }

    /**
     * Extracts the name and type of the group from the filename.
     *
     * @param string $filename The filename to parse.
     * @return array with ['type':'type','name':'name'].
     */
    public static function parseFilename($filename)
    {
        list($type, $name) = explode('.', $filename);
        return compact('type', 'name');
    }
}

