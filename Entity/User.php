<?php
/**
 * @file
 *  Gitolite User
 */
namespace Gitolite\Entity;
use Gitolite\File;

class User extends Entity
{
    public function __construct($username, array $files = array())
    {
        parent::__construct('User', $username, $files);
    }

    /**
     * Adds a key for this user and optionally commits to the gitolite admin repository.
     *
     * @param string $reference The reference which uniquely identifies this key for this user.
     * @param string $value The value of the actual key.
     */
    public function addKey($reference, $value)
    {
        $filename = $this->keyFilename($reference);
        $key = new File($filename, $value);
        $this->addFile($key);
        return TRUE;
    }

    /**
     * Adds all the keys that belong to this user.
     *
     * @param array $keys An array of keys of the format ['reference':'reference', 'value':'value'].
     */
    public function addKeys(array $keys)
    {
        foreach ($keys as $key) {
            $this->addKey($key['reference'], $key['value']);
        }
    }

    /**
     * Removes a key for this user and commits to the gitolite admin repository.
     *
     * @param string $reference The reference to the key to remove.
     */
    public function deleteKey($reference)
    {
        $filename = $this->keyFilename($reference);
        $this->getFile($filename)->setDeleted(TRUE);
    }

    /**
     * Returns the name of a key for this user based on the key reference.
     *
     * @param string $reference The key reference.
     */
    protected function keyFilename($reference)
    {
        return $this->getName() . "@$reference.pub";
    }

    /**
     * Returns the username and reference included in the key filename.
     *
     * @param string $filename The filename to parse.
     * @return array with ['username':'username', 'reference':'reference'].
     */
    public static function parseKeyFilename($filename)
    {
        if (preg_match('/(.*?)@([^.]+).pub$/', $filename, $matches)) {
            return array(
                'username' => $matches[1],
                'reference' => $matches[2],
            );
        }
        return FALSE;
    }

    /**
     * Load the user from the files already attached.
     */
    public function load()
    {
    }
}
