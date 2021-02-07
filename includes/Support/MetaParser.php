<?php
namespace App\Support;

class MetaParser
{
    private FileSystemContract $fileSystem;

    public function __construct(FileSystemContract $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param string $path
     * @return array
     */
    public function parse($path): array
    {
        $lines = explode(PHP_EOL, $this->fileSystem->get($path));

        return collect($lines)
            ->flatMap(function ($line) {
                [$key, $value] = explode("=", $line);
                return [trim($key) => trim($value)];
            })
            ->all();
    }
}
