<?php
namespace App\Theme;

use App\Support\Collection;
use App\Support\FileSystemContract;
use App\Support\BasePath;
use Generator;

class ThemeRepository
{
    private TemplateRepository $templateRepository;
    private FileSystemContract $fileSystem;
    private BasePath $path;

    public function __construct(
        TemplateRepository $templateRepository,
        FileSystemContract $fileSystem,
        BasePath $path
    ) {
        $this->templateRepository = $templateRepository;
        $this->fileSystem = $fileSystem;
        $this->path = $path;
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return $this->listAll()
            ->sort()
            ->unique()
            ->values()
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
