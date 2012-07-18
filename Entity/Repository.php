<?php
/**
 * @file
 *  Gitolite Repository
 */
namespace Gitolite\Entity;
use Gitolite\File;

class Repository extends Entity
{
    protected $rules;

    /**
     * Constructs a new group.
     *
     * @param string $name The group's name.
     * @param array $items The items in the group.
     */
    public function __construct($name, $file = NULL)
    {
        $files = array();
        if ($file instanceof File) {
            $files[] = $file;
        }
        $this->rules = array();
        parent::__construct('Repository', $name, $files);
    }

    /**
     * Adds an rule to the repo.
     *
     * @param Gitolite\Entity\Rule $rule The rule to add to the repo.
     */
    public function addRule(Rule $rule)
    {
        $this->removeRule($rule);
        $this->rules[] = $rule;
    }

    /**
     * Removes an rule from the repo.
     *
     * @param string|Gitolite\Entity\Rule $rule The rule to remove.
     */
    public function removeRule(Rule $rule)
    {
        $index = array_search($rule, $this->rules);
        if ($index) {
            unset($this->rules[$index]);
        }
        return TRUE;
    }

    /**
     * Removes all rules.
     */
    public function removeAllRules()
    {
        $this->rules = array();
    }

    /**
     * Returns the rules in this reop.
     *
     * @return array of the rules in the repo.
     */
    public function getRules()
    {
        return $this->rules;
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
        $repoConfig = array_shift($config);
        $rulesConfig = $config;

        list($name) = sscanf($repoConfig, 'repo %s');
        $this->setName($name);

        $this->rules = array();
        foreach ($rulesConfig as $ruleLine) {
            $ruleLine = preg_replace('/#.*/', '', $ruleLine);
            $parts = explode('=', $ruleLine);

            $entity = new Placeholder(trim($parts[1]));
            $parts = preg_split("/\s+/", trim($parts[0]));
            $permission = array_shift($parts);
            $refexes = $parts;
            $this->addRule(new Rule($permission, $refexes, $entity));
        }
    }

    /**
     * Update the file associated with this repo and return it.
     *
     * @return array of Gitolite\File objects
     */
    public function getFiles()
    {
        $config = 'repo ' . $this->getName() . "\n";
        foreach ($this->getRules() as $rule) {
            $config .= $rule . "\n";
        }

        $filename = $this->filename();
        $file = new File($filename, $config);
        $this->files = array($filename => $file);
        return parent::getFiles();
    }

    /**
     * Gets the filename of this repo conf file.
     *
     * @return string The repo's filename.
     */
    public function filename()
    {
        return self::formatFilename($this->getName());
    }

    /**
     * Formats a filename for a repo given the name.
     *
     * @param string $name The name of the repo.
     */
    public static function formatFilename($name)
    {
        return strtolower(sprintf('%s.conf', $name));
    }
}

