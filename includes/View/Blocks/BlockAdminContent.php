<?php
namespace App\View\Blocks;

use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Pages\Admin\PageAdmin;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class BlockAdminContent extends Block
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    public function __construct(Heart $heart, TranslationManager $translationManager)
    {
        $this->heart = $heart;
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

        $page = $params[0];

        if (!($page instanceof PageAdmin)) {
            $page = $this->heart->getPage($page);
        }

        if (!$page) {
            throw new UnexpectedValueException("No page provided");
        }

        if (!has_privileges($page->getPrivilege())) {
            return $this->lang->t("no_privileges");
        }

        return $page->getContent($request);
    }
}
