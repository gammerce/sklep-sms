<?php
namespace App\View;

use App\Routing\UrlGenerator;
use App\Support\FileSystem;
use App\Support\Path;
use App\View\Pages\Page;

class WebsiteHeader
{
    /** @var Path */
    private $path;

    /** @var FileSystem */
    private $fileSystem;

    /** @var UrlGenerator */
    private $url;

    /** @var string[] */
    private $scripts;

    /** @var string[] */
    private $styles;

    public function __construct(Path $path, FileSystem $fileSystem, UrlGenerator $url)
    {
        $this->path = $path;
        $this->fileSystem = $fileSystem;
        $this->url = $url;
    }

    public function addPageScripts(Page $page)
    {
        $path = "build/js/shop/pages/{$page->getPageId()}/";
        if ($this->fileSystem->exists($this->path->to($path))) {
            foreach ($this->fileSystem->scanDirectory($this->path->to($path)) as $file) {
                if (ends_at($file, ".js")) {
                    $this->addScript($this->url->versioned($path . $file));
                }
            }
        }
    }

    /**
     * Add JS script
     *
     * @param string $path
     */
    public function addScript($path)
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
    public function addStyle($path)
    {
        if (!in_array($path, $this->styles)) {
            $this->styles[] = $path;
        }
    }

    public function getScripts()
    {
        return collect($this->scripts)
            ->map(function ($path) {
                return "<script type=\"text/javascript\" src=\"{$path}\"></script>";
            })
            ->join("\n");
    }

    public function getStyles()
    {
        return collect($this->styles)
            ->map(function ($path) {
                return "<link href=\"{$path}\" rel=\"stylesheet\" />";
            })
            ->join("\n");
    }
}
