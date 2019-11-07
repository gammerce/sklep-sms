<?php
namespace App\System;

use ErrorException;
use Exception;

class Filesystem
{
    /**
     * @param string $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function delete($path)
    {
        try {
            if (!@unlink($path)) {
                return false;
            }
        } catch (ErrorException $e) {
            return false;
        }

        return true;
    }

    /**
     * Create a directory.
     *
     * @param  string $path
     * @param  int    $mode
     * @param  bool   $recursive
     * @param  bool   $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }
        return mkdir($path, $mode, $recursive);
    }

    /**
     * Write the contents of a file.
     *
     * @param  string $path
     * @param  string $contents
     * @param  bool   $lock
     * @return int
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Get the contents of a file.
     *
     * @param  string $path
     * @param  bool   $lock
     * @return string
     *
     * @throws Exception
     */
    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }
        throw new Exception("File does not exist at path {$path}");
    }

    /**
     * Get contents of a file with shared access.
     *
     * @param  string $path
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';
        $handle = fopen($path, 'rb');
        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);
                    $contents = fread($handle, $this->size($path) ?: 1);
                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }
        return $contents;
    }

    /**
     * Determine if the given path is a file.
     *
     * @param  string $file
     * @return bool
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Get the file size of a given file.
     *
     * @param  string $path
     * @return int
     */
    public function size($path)
    {
        return filesize($path);
    }
}
