<?php
namespace App\Managers;

use App\Routing\UrlGenerator;
use App\Support\FileSystem;
use App\Support\Path;

class WebsiteHeader
{
    private Path $path;
    private FileSystem $fileSystem;
    private UrlGenerator $url;

    /** @var string[] */
    private array $scripts = [];

    /** @var string[] */
    private array $styles = [];

    public function __construct(Path $path, FileSystem $fileSystem, UrlGenerator $url)
    {
        $this->path = $path;
        $this->fileSystem = $fileSystem;
        $this->url = $url;
    }

    /**
     * Add JS script
     *
     * @param string $path
     */
    public function addScript($path): void
    {
        if (!in_array($path, $this->scripts)) {
            $this->scripts[] = $path;
        }
    }

    /**
     * Add CSS stylesheet
     *
     * @param string $path
     */
    public function addStyle($path): void
    {
        if (!in_array($path, $this->styles)) {
            $this->styles[] = $path;
        }
    }

    public function getScripts(): string
    {
        return collect($this->scripts)
            ->map(fn($path) => "<script type=\"text/javascript\" src=\"{$path}\"></script>")
            ->join("\n");
    }

    public function getStyles(): string
    {
        return collect($this->styles)
            ->map(fn($path) => "<link href=\"{$path}\" rel=\"stylesheet\" />")
            ->join("\n");
    }
}
