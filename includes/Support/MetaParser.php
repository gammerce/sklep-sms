<?php
namespace App\Support;

class MetaParser
{
    private FileSystemContract $fileSystem;

    public function __construct(FileSystemContract $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    public function parse(string $path): array
    {
        $lines = preg_split('/[\n\r]/', $this->fileSystem->get($path));

        return collect($lines)
            ->flatMap(function ($line) {
                $exploded = explode("=", $line);

                if (count($exploded) != 2) {
                    return [];
                }

                return [trim($exploded[0]) => trim($exploded[1])];
            })
            ->all();
    }
}
