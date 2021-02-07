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
                $exploded = explode("=", $line);

                if (count($exploded) != 2) {
                    return [];
                }

                return [trim($exploded[0]) => trim($exploded[1])];
            })
            ->all();
    }
}
