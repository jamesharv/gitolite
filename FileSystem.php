<?php
/**
 * @file
 *  Gitolite FileSystem abstraction
 */
namespace Gitolite;

use Gitolite\Entity\Entity,
    Gitolite\Entity\Group,
    Gitolite\Entity\User,
    Gitolite\Entity\Repository;

class FileSystem
{
    protected $directory;
    protected $paths = array(
      'keys'          => 'keydir',
      'configuration' => 'conf/gitolite.conf',
      'groups'        => 'conf/managed/groups',
      'repositories'  => 'conf/managed/repos',
    );
    protected $includes = array(
        'managed/groups/*.conf' => 'managed\/groups\/\*\.conf',
        'managed/repos/*.conf' => 'managed\/repos\/\*\.conf',
    );

    /**
     * Constructs a file system.
     *
     * @param string $directory The root directory for the file system.
     * @param array $paths The paths where files are located. Only used if $includes is also included.
     * @param array $includes
     *  The includes which should be added to the gitolite.conf.
     *  key is the include statement, value is a regex to test if the include is already present (eg. ['groups/*.conf':'repos\/\*\.conf'])
     */
    public function __construct($directory, $paths = NULL, $includes = NULL)
    {
        $this->directory = $directory;
        if (is_array($paths) && is_array($includes)) {
          $this->paths = $paths;
          $this->includes = $includes;
        }
    }

   /**
     * Provides the absolute path to the specified resource.
     *
     * @return string The directory in which resource lives.
     */
    public function getPath($resource)
    {
        return $this->directory . '/' . $this->paths[$resource];
    }

    /**
     * Gets the path where the given entity should be saved.
     *
     * @param Gitolite\Entity\Entity | string The entity itself or the entity's classname.
     */
    public function getEntitySavePath($entity)
    {
        if ($entity instanceof Entity) {
            $entity = $entity->getEntityType();
        }
        switch((string) $entity) {
            case 'User':
                return $this->getPath('keys');
            case 'Group':
                return $this->getPath('groups');
            case 'Repository':
                return $this->getPath('repositories');
        }
    }

    /**
     * Ensure that the necessary includes have been added to the gitolite config file.
     *
     * @return bool|string FALSE if no changes were made otherwise the path to the config file.
     */
    public function setupIncludes()
    {
        $filepath = $this->getPath('configuration');
        $config = file_get_contents($filepath);

        $missing_includes = array();
        foreach ($this->includes as $include => $regexp) {
            if (!preg_match('/include\s+"' . $regexp . '"/', $config, $matches)) {
                $missing_includes[] = "include \"$include\"";
            }
        }
        if (count($missing_includes) == 0) {
            return FALSE;
        }

        $config .= "\n" . implode("\n", $missing_includes);
        if (FALSE === file_put_contents($filepath, $config)) {
            throw new \RuntimeException("Could not save config");
        }
        return $filepath;
    }

    /**
     * Returns a reference to a user with the given username.
     *
     * @return Gitolite\User
     */
    public function loadUser($username)
    {
        $keyFilePaths = $this->findKeyFiles($username);
        $keyFiles = array();

        if ($keyFilePaths) {
            foreach ($keyFilePaths as $keyFilePath) {
                $file = $this->loadFile($keyFilePath);
                if (!is_null($file)) {
                    $keyFiles[] = $file;
                }
            }
        }

        $user = new User($username, $keyFiles);
        return $user;
    }

    /**
     * Loads all users in the admin repo.
     *
     * @return array of Gitolite\User objects.
     */
    public function loadUsers()
    {
        $users = array();
        $keyFilePaths = $this->findKeyFiles();

        foreach ($keyFilePaths as $keyFilePath) {
            $parsed = User::parseKeyFilename(basename($keyFilePath));
            if ($parsed) {
                $username = $parsed['username'];
                if (!isset($users[$username])) {
                    $users[$username] = $this->loadUser($parsed['username']);
                }
            }
        }

        return $users;
    }

    /**
     * Returns an array of key filepaths.
     *
     * @param string $username The optional username to search for.
     * @return array of absolute key filepath strings.
     */
    public function findKeyFiles($username = '*')
    {
        $keyDir = $this->getEntitySavePath('User');
        $keyFiles = glob("$keyDir/$username@*.pub");
        return $keyFiles;
    }

    /**
     * Load a group object from the file system.
     *
     * @param string $groupName The name of the group.
     * @param string $groupType The type of group (User|Repository).
     *
     * @return Gitolite\Group
     */
    public function loadGroup($groupName, $groupType)
    {
        $filename = Group::formatFilename($groupName, $groupType);
        $file = $this->loadFile($this->getEntitySavePath('Group') . "/$filename");
        $group = new Group($groupName, $groupType, $file);
        return $group;
    }

    /**
     * Load a group object from the file system.
     *
     * @param string $groupType The type of group (User|Repository).
     *
     * @return Gitolite\Group
     */
    public function loadGroups($groupType)
    {
        $search = $this->getEntitySavePath('Group') . '/' . Group::formatFilename('*', $groupType);
        $groups = array();
        foreach (glob($search) as $filename) {
            $parsed = Group::parseFilename(basename($filename));
            $file = $this->loadFile($filename);
            $groups[] = new Group($parsed['name'], $groupType, $file);
        }
        return $groups;
    }

    /**
     * Load a repo object from the file system.
     *
     * @param string $repoName The name of the group.
     *
     * @return Gitolite\Entity\Repository
     */
    public function loadRepository($repoName)
    {
        $filename = Repository::formatFilename($repoName);
        $file = $this->loadFile($this->getEntitySavePath('Repository') . "/$filename");
        $repo = new Repository($repoName, $file);
        return $repo;
    }

    /**
     * Saves the entity to the file system.
     *
     * @param Gitolite\Entity $entity The entity to save.
     * @throws RuntimeException if save fails.
     */
    public function save(Entity $entity)
    {
        $directory = $this->getEntitySavePath($entity);

        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, TRUE)) {
                throw new \RuntimeException("Could not create " . $entity->getEntityType() . " directory $directory");
            }
        }
        elseif (!is_dir($directory)) {
            throw new \RuntimeException("$directory is not a directory");
        }

        $filePaths = array('added' => array(), 'deleted' => array());
        foreach ($entity->getFiles() as $file) {
            $filePath = "$directory/" . $file->getFilename();
            if ($file->isDeleted()) {
                if (!unlink($filePath)) {
                    throw new \RuntimeException("Could not delete file $filePath");
                }
                $entity->removeFile($file);
                $filePaths['deleted'][] = $filePath;
                continue;
            }

            $current_contents = file_exists($filePath) ? file_get_contents($filePath) : '';
            $new_contents = $file->getContents();
            if ($new_contents == $current_contents) {
                continue;
            }

            if (FALSE === file_put_contents($filePath, $new_contents)) {
                throw new \RuntimeException("Could not save " . $entity->getType() . " " . $entity->getName() . " to $filePath");
            }
            $filePaths['added'][] = $filePath;
        }

        return $filePaths;
    }

    /**
     * Delete the entity from the file system.
     *
     * @param Gitolite\Entity $entity The entity to delete.
     */
    public function delete(Entity $entity)
    {
        $directory = $this->getEntitySavePath($entity);

        $filePaths = array();
        foreach ($entity->getFiles() as $file) {
            $filePaths[] = $filePath = "$directory/" . $file->getFilename();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return $filePaths;
    }

    /**
     * Loads a file object from it's filepath.
     *
     * @param string $filepath The path to the file to load.
     * @throws RuntimeException if file cannot be loaded.
     */
    protected function loadFile($filepath)
    {
        $file = NULL;
        if (file_exists($filepath)) {
            $contents = file_get_contents($filepath);
            if (FALSE === $contents) {
                throw new \RuntimeException("Could not load file $filepath");
            }
            $file = new File(basename($filepath), $contents);
        }
        return $file;
    }
}

