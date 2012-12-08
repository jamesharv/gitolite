<?php
/**
 * @file
 *  Gitolite model class
 */
namespace Gitolite;
use Gitter\Repository;
use Gitolite\Entity\Entity;

class Client
{
    protected $adminRepository;
    protected $fileSystem;
    protected $cache = array();

    /**
     * Constructs a Gitolite\Gitolite instance
     *
     * @param Git\Repository $adminRepository The Gitolite admin repository.
     */
    public function __construct(Repository $adminRepository, FileSystem $fileSystem)
    {
        $this->setAdminRepository($adminRepository);
        $this->setFileSystem($fileSystem);
    }

    /**
     * Sets the gitolte admin repository.
     *
     * @param Git\Repository $adminRepository The gitolite admin repsitory
     */
    public function setAdminRepository(Repository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    /**
     * Gets the gitolite admin repository.
     *
     * @return Git\Repository The admin repository.
     */
    public function getAdminRepository()
    {
        return $this->adminRepository;
    }

    /**
     * Sets the file system to use for this client.
     *
     * @param FileSystem $fileSystem The file system for this client to use.
     */
    public function setFileSystem(FileSystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * Returns this client's file system.
     *
     * @param FileSystem $fileSystem The file system for this client to use.
     */
    public function getFileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * Push changes to the remote. This is necessary for changes to be applied.
     */
    public function push()
    {
        $this->getAdminRepository()->push();
    }

    /**
     * Returns a reference to a user with the given username.
     *
     * @return Gitolite\Entity\User
     */
    public function loadUser($username)
    {
        $username = $this->slug($username);
        return $this->cacheGet('User', $username) ?: $this->cacheSet($this->getFileSystem()->loadUser($username));
    }

    /**
     * Loads all users in the admin repo. Forces a cache clear.
     *
     * @return array of Gitolite\Entity\User objects.
     */
    public function loadUsers()
    {
        return $this->cacheSet($this->getFileSystem()->loadUsers());
    }

    /**
     * Load a group object from the file system.
     *
     * @param string $groupName The name of the group.
     * @param string $groupType The type of group (User|Repository).
     *
     * @return Gitolite\Entity\Group
     */
    public function loadGroup($groupName, $groupType)
    {
        $groupName = $this->slug($groupName);
        return $this->cacheGet('Group', "$groupType:$groupName") ?: $this->cacheSet($this->getFileSystem()->loadGroup($groupName, $groupType));
    }

    /**
     * Loads all groups in the admin repo. Forces a cache clear.
     *
     * @return array of Gitolite\Entity\Group objects.
     */
    public function loadGroups($groupType)
    {
        return $this->cacheSet($this->getFileSystem()->loadGroups($groupType));
    }

    /**
     * Load a repo object from the file system.
     *
     * @param string $repoName The name of the repo.
     *
     * @return Gitolite\Entity\Repository
     */
    public function loadRepository($repoName)
    {
        $repoName = $this->slug($repoName);
        return $this->cacheGet('Repository', $repoName) ?: $this->cacheSet($this->getFileSystem()->loadRepository($repoName));
    }

    /**
     * Saves a given Entity or set of entities.
     *
     * @param array|Gitolite\Entity\Entity $entities The entity / entities to save.
     * @param string|bool $commitMessage A description of this change. If empty changes will not be committed. Defaults to FALSE.
     */
    public function save($entities, $commitMessage = FALSE)
    {
        if (!is_array($entities)) {
            $entities = array($entities);
        }
        foreach ($entities as $entity) {
            if (!$entity instanceof Entity) {
                throw new \RuntimeException("Client::save() given a non Entity objects.");
            }
        }

        $fileSystem = $this->getFileSystem();
        $filesChanged = FALSE;
        foreach ($entities as $entity) {
            $filePaths = $fileSystem->save($entity);
            if (count($filePaths['added']) > 0) {
                $filesChanged = TRUE;
                $this->getAdminRepository()->add($filePaths['added']);
            }
            if (count($filePaths['deleted']) > 0) {
                $filesChanged = TRUE;
                $this->getAdminRepository()->remove($filePaths['deleted']);
            }
        }

        if ($commitMessage && $filesChanged) {
            $this->getAdminRepository()->commit($commitMessage);
        }
    }

    /**
     * Deletes a given Entity or set of entities.
     *
     * @param array|Gitolite\Entity\Entity $entities The entity / entities to delete.
     * @param string|bool $commitMessage A description of this change. If empty changes will not be committed. Defaults to FALSE.
     */
    public function delete($entities, $commitMessage = FALSE)
    {
        if (!is_array($entities)) {
            $entities = array($entities);
        }
        foreach ($entities as $entity) {
            if (!$entity instanceof Entity) {
                throw new \RuntimeException("Client::delete() given a non Entity objects.");
            }
        }

        $fileSystem = $this->getFileSystem();
        $filesChanged = FALSE;
        foreach ($entities as $entity) {
            $filePaths = $fileSystem->delete($entity);
            $this->cacheUnset($entity);
            if (count($filePaths) > 0) {
                $filesChanged = TRUE;
                $this->getAdminRepository()->remove($filePaths);
            }
        }

        if ($commitMessage && $filesChanged) {
            $this->getAdminRepository()->commit($commitMessage);
        }
    }

    /**
     * Slugifies the string.
     *
     * @param string $string the string to slug.
     * @return string The slug.
     */
    public function slug($string)
    {
        return trim(preg_replace('#[^A-Za-z0-9@._]+#', '-', $string), '-');
    }

    /**
     * Sets up assumed config.
     * Only needs to be run once, but can be run again without doing any damage.
     */
    public function install()
    {
        $configFile = $this->getFileSystem()->setupIncludes();
        if ($configFile) {
            $repository = $this->getAdminRepository();
            $repository->add($configFile);
            $repository->commit("Installed", array(), array($configFile));
            $repository->push();
        }
    }

    /**
     * Add entities to cache.
     */
    protected function cacheSet($entities)
    {
        $array = is_array($entities) ? $entities : array($entities);
        foreach ($array as $entity) {
            $this->cache[$entity->getEntityType()][$entity->getId()] = $entity;
        }
        return $entities;
    }

    /**
     * Unset entities in the cache.
     */
    protected function cacheUnset($entities)
    {
        $entities = is_array($entities) ? $entities : array($entities);
        foreach ($entities as $entity) {
            if (isset($this->cache[$entity->getEntityType()][$entity->getId()])) {
                unset($this->cache[$entity->getEntityType()][$entity->getId()]);
            }
        }
    }

    /**
     * Get an entity from the cache.
     */
    protected function cacheGet($entityType, $entityId, $default = NULL)
    {
        if (isset($this->cache[$entityType][$entityId])) {
            return $this->cache[$entityType][$entityId];
        }
        return $default;
    }
}
