<?php
namespace App\View\Renders;

use App\Managers\PageManager;
use App\Routing\UrlGenerator;
use App\System\Auth;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PageLinkRenderer
{
    private Auth $auth;
    private PageManager $pageManager;
    private Template $template;
    private Translator $lang;
    private UrlGenerator $url;

    public function __construct(
        Auth $auth,
        PageManager $pageManager,
        TranslationManager $translationManager,
        Template $template,
        UrlGenerator $url
    ) {
        $this->auth = $auth;
        $this->pageManager = $pageManager;
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->url = $url;
    }

    /**
     * @param string $pageId
     * @param string $activePageId
     * @param array $query
     * @return string
     */
    public function renderLink($pageId, $activePageId, array $query = []): ?string
    {
        $page = $this->pageManager->getAdmin($pageId);
        if ($this->auth->user()->cannot($page->getPrivilege())) {
            return null;
        }

        $name = $page->getTitle();
        $path = $this->url->to("/admin/$pageId", $query);
        $isActiveClass = $pageId === $activePageId ? "is-active" : null;

        return $this->template->render("admin/page_link", compact("path", "name", "isActiveClass"));
    }
}
