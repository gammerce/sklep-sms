<?php
namespace App\View\Pages;

use App\Support\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Html\I_ToHtml;
use Symfony\Component\HttpFoundation\Request;

abstract class Page
{
    const PAGE_ID = "";

    /** @var Template */
    protected $template;

    /** @var Translator */
    protected $lang;

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
    abstract public function getTitle(Request $request);

    /**
     * Get page content
     *
     * @param Request $request
     * @return I_ToHtml|string
     */
    abstract public function getContent(Request $request);

    public function getId()
    {
        return $this::PAGE_ID;
    }

    public function getPagePath()
    {
        return "/page/{$this->getId()}";
    }
}
