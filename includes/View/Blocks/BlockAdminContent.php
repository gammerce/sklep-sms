<?php
namespace App\View\Blocks;

use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Pages\PageAdmin;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class BlockAdminContent extends Block
{
    /** @var Translator */
    private $lang;

    public function __construct(TranslationManager $translationManager)
    {
        $this->lang = $translationManager->user();
    }

    public function getContentId()
    {
        return "content";
    }

    public function getContentClass()
    {
        return "";
    }

    protected function content(Request $request, array $params)
    {
        if (!is_logged()) {
            return $this->lang->t('must_be_logged_in');
        }

        /** @var PageAdmin $page */
        $page = $params[0];

        if (!$page) {
            throw new UnexpectedValueException("No page provided");
        }

        if (!has_privileges($page->getPrivilege())) {
            return $this->lang->t("no_privileges");
        }

        return $page->getContent($request);
    }
}
