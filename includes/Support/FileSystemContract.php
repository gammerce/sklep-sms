<?php
namespace App\Support;

use Exception;

interface FileSystemContract
{
    /**
     * @param string $path
     * @return bool
     */
    public function exists($path);

    /**
     * @param string $path
     * @return bool
     */
    public function delete($path);

    /**
     * Create a directory.
     *
     * @param  string $path
     * @param  int    $mode
     * @param  bool   $recursive
     * @param  bool   $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false);

    /**
     * Write the contents of a file.
     *
     * @param  string $path
     * @param  string $contents
     * @param  bool   $lock
     * @return int
     */
    public function put($path, $contents, $lock = false);

    /**
     * Get the contents of a file.
     *
     * @param  string $path
     * @param  bool   $lock
     * @return string
     *
     * @throws Exception
     */
    public function get($path, $lock = false);

    /**
     * Get contents of a file with shared access.
     *
     * @param  string $path
     * @return string
     */
    public function sharedGet($path);

    /**
     * Determine if the given path is a file.
     *
     * @param  string $path
     * @return bool
     */
    public function isFile($path);

    /**
     * Determine if the given path is a directory.
     *
     * @param  string $path
     * @return bool
     */
    public function isDirectory($path);

    /**
     * Get the file size of a given file.
     *
     * @param  string $path
     * @return int
     */
    public function size($path);

    /**
     * Add text to the end of the file
     *
     * @param string $file
     * @param string $text
     */
    public function append($file, $text);

    /**
     * @param string $path
     * @param int    $mode
     * @return bool
     */
    public function setPermissions($path, $mode);

    /**
     * @param string $path
     * @return int
     */
    public function getPermissions($path);

    /**
     * @param $path
     * @return string[]|false
     */
    public function scanDirectory($path);

    /**
     * @param string $path
     * @return bool
     */
    public function isWritable($path);
}
