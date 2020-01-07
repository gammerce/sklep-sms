<?php
namespace Tests\Psr4;

use App\System\FileSystemContract;
use Exception;

class MemoryFileSystem implements FileSystemContract
{
    private $fileSystem = [];

    public function exists($path)
    {
        $formattedPath = $this->formatPath($path);
        return array_key_exists($formattedPath, $this->fileSystem);
    }

    public function delete($path)
    {
        $formattedPath = $this->formatPath($path);

        if ($this->exists($formattedPath)) {
            unset($this->fileSystem[$formattedPath]);
            return true;
        }

        return false;
    }

    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        $formattedPath = $this->formatPath($path);

        $this->fileSystem[$formattedPath] = [
            'type' => 'd',
            'mode' => $mode,
        ];
    }

    public function put($path, $contents, $lock = false)
    {
        $formattedPath = $this->formatPath($path);

        $this->fileSystem[$formattedPath] = [
            'type' => 'f',
            'content' => $contents,
            'mode' => 0755,
        ];
    }

    public function get($path, $lock = false)
    {
        $formattedPath = $this->formatPath($path);

        if ($this->isFile($formattedPath)) {
            return $this->fileSystem[$formattedPath]['content'];
        }

        throw new Exception("File does not exist at path {$formattedPath}");
    }

    public function sharedGet($path)
    {
        return $this->get($path);
    }

    public function isFile($path)
    {
        $formattedPath = $this->formatPath($path);
        return $this->exists($formattedPath) && $this->fileSystem[$formattedPath]['type'] === 'f';
    }

    public function isDirectory($path)
    {
        $formattedPath = $this->formatPath($path);
        return $this->exists($formattedPath) && $this->fileSystem[$formattedPath]['type'] === 'd';
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
        $formattedPath = $this->formatPath($path);

        if ($this->exists($formattedPath)) {
            $this->fileSystem[$formattedPath]['mode'] = $mode;
            return true;
        }

        return false;
    }

    public function getPermissions($path)
    {
        $formattedPath = $this->formatPath($path);
        return $this->exists($formattedPath) ? $this->fileSystem[$formattedPath]['mode'] : false;
    }

    public function scanDirectory($path)
    {
        if (!$this->exists($path)) {
            return false;
        }

        $formattedPath = $this->formatPath($path);

        $output = [];

        foreach (array_keys($this->fileSystem) as $key) {
            if (starts_with($formattedPath, $key) && $this->isFile($key)) {
                $output[] = $key;
            }
        }

        return $output;
    }

    public function isWritable($path)
    {
        return $this->exists($path);
    }

    private function formatPath($path)
    {
        return rtrim($path, DIRECTORY_SEPARATOR);
    }
}
