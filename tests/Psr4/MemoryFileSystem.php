<?php
namespace Tests\Psr4;

use App\System\FileSystemContract;
use Exception;

class MemoryFileSystem implements FileSystemContract
{
    private $fileSystem = [];

    public function exists($path)
    {
        return array_key_exists($path, $this->fileSystem);
    }

    public function delete($path)
    {
        unset($this->fileSystem[$path]);
        return true;
    }

    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        $this->fileSystem[$path] = [
            'type' => 'd',
            'mode' => $mode,
        ];
    }

    public function put($path, $contents, $lock = false)
    {
        $this->fileSystem[$path] = [
            'type' => 'f',
            'content' => $contents,
            'mode' => 0755,
        ];
    }

    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $this->fileSystem[$path]['content'];
        }

        throw new Exception("File does not exist at path {$path}");
    }

    public function sharedGet($path)
    {
        return $this->get($path);
    }

    public function isFile($file)
    {
        return $this->exists($file) && $this->fileSystem[$file]['type'] === 'd';
    }

    public function size($path)
    {
        return $this->exists($path) ? 1 : 0;
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
        if ($this->exists($path)) {
            $this->fileSystem[$path]['mode'] = $mode;
            return true;
        }

        return false;
    }

    public function getPermissions($path)
    {
        return $this->exists($path) ? $this->fileSystem[$path]['mode'] : false;
    }
}
