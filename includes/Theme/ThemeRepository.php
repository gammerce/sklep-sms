<?php
namespace App\Theme;

use App\Support\Collection;
use App\Support\FileSystemContract;
use App\Support\Path;
use Generator;

class ThemeRepository
{
    private TemplateRepository $templateRepository;
    private FileSystemContract $fileSystem;
    private Path $path;

    public function __construct(
        TemplateRepository $templateRepository,
        FileSystemContract $fileSystem,
        Path $path
    ) {
        $this->templateRepository = $templateRepository;
        $this->fileSystem = $fileSystem;
        $this->path = $path;
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return $this->listAll()
            ->sort()
            ->unique()
            ->all();
    }

    public function exists($name): bool
    {
        return $this->listAll()->some(fn($theme) => $theme === $name);
    }

    private function listAll(): Collection
    {
        return collect($this->templateRepository->listThemes())->extend(
            $this->listFilesystemThemes()
        );
    }

    private function listFilesystemThemes(): Generator
    {
        $dirList = $this->fileSystem->scanDirectory($this->path->to("themes"));

        foreach ($dirList as $dirName) {
            if (
                $dirName[0] != "." &&
                $this->fileSystem->isDirectory($this->path->to("themes/$dirName"))
            ) {
                yield $dirName;
            }
        }
    }
}
