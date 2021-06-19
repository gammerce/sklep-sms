<?php
namespace App\View\Pages;

use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\Support\FileSystem;
use App\Support\Path;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Html\I_ToHtml;
use Symfony\Component\HttpFoundation\Request;

abstract class Page
{
    protected Template $template;
    protected Translator $lang;

    public function __construct(Template $template, TranslationManager $translationManager)
    {
        $this->template = $template;
        $this->lang = $translationManager->user();
    }

    /**
     * Get page title
     *
     * @param Request $request
     * @return string
     */
    abstract public function getTitle(Request $request): string;

    /**
     * Get page content
     *
     * @param Request $request
     * @return I_ToHtml|string
     */
    abstract public function getContent(Request $request);

    public function getId(): string
    {
        return $this::PAGE_ID;
    }

    public function getPagePath(): string
    {
        return "/page/{$this->getId()}";
    }

    public function addScripts(Request $request): void
    {
        /** @var FileSystem $fileSystem */
        $fileSystem = app()->make(FileSystem::class);

        /** @var Path $path */
        $path = app()->make(Path::class);

        /** @var UrlGenerator $url */
        $url = app()->make(UrlGenerator::class);

        /** @var WebsiteHeader $websiteHeader */
        $websiteHeader = app()->make(WebsiteHeader::class);

        $scriptPath = "build/js/shop/pages/{$this->getId()}/";
        if ($fileSystem->exists($path->to($scriptPath))) {
            foreach ($fileSystem->scanDirectory($path->to($scriptPath)) as $file) {
                if (ends_at($file, ".js")) {
                    $websiteHeader->addScript($url->versioned($scriptPath . $file));
                }
            }
        }
    }
}
