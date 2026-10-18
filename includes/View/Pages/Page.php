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
     * @param Request|null $request
     * @return string
     */
    abstract public function getTitle(?Request $request = null): string;

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

    /**
     * Reads a one-shot message key from the session (and removes it).
     */
    protected function pullSessionMessage(Request $request, string $key): string
    {
        $session = $request->getSession();

        if (!$session->has($key)) {
            return "";
        }

        $message = $this->lang->t((string) $session->get($key));
        $session->remove($key);

        return $message;
    }

    /**
     * Reads a message key from the query string. When $allowed is provided only
     * whitelisted keys are translated.
     *
     * @param string[] $allowed
     */
    protected function queryMessage(Request $request, string $key, array $allowed = []): string
    {
        $value = (string) $request->query->get($key, "");

        if ($value === "" || ($allowed && !in_array($value, $allowed, true))) {
            return "";
        }

        return $this->lang->t($value);
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
                if (str_ends_with($file, ".js")) {
                    $websiteHeader->addScript($url->versioned($scriptPath . $file));
                }
            }
        }
    }
}
