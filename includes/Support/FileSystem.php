<?php
namespace App\Support;

use ErrorException;
use Exception;

class FileSystem implements FileSystemContract
{
    public function exists($path)
    {
        return file_exists($path);
    }

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

    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }
        return mkdir($path, $mode, $recursive);
    }

    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }
        throw new Exception("File does not exist at path {$path}");
    }

    public function sharedGet($path)
    {
        $contents = "";
        $handle = fopen($path, "rb");
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

    public function isFile($path)
    {
        return is_file($path);
    }

    public function isDirectory($path)
    {
        return is_dir($path);
    }

    public function size($path)
    {
        return filesize($path);
    }

    public function append($file, $text)
    {
        if ($this->exists($file) && strlen($this->get($file))) {
            $text = $this->get($file) . "\n" . $text;
        }

        $this->put($file, $text);
    }

    public function setPermissions($path, $mode)
    {
        return chmod($path, $mode);
    }

    public function getPermissions($path)
    {
        return fileperms($path);
    }

    public function scanDirectory($path)
    {
        return scandir($path);
    }

    public function isWritable($path)
    {
        return is_writable($path);
    }

    public function lastChangedAt($path)
    {
        return filectime($path);
    }
}
