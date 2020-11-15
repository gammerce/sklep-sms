<?php
namespace App\View\Pagination;

use App\Routing\UrlGenerator;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaginationFactory
{
    /** @var Settings */
    private $settings;

    /** @var UrlGenerator */
    private $url;

    /** @var TranslationManager */
    private $translationManager;

    public function __construct(
        Settings $settings,
        UrlGenerator $url,
        TranslationManager $translationManager
    ) {
        $this->settings = $settings;
        $this->url = $url;
        $this->translationManager = $translationManager;
    }

    /**
     * @param Request $request
     * @return Pagination
     */
    public function create(Request $request)
    {
        return new Pagination(
            $this->url,
            $this->settings,
            $this->translationManager->user(),
            $request
        );
    }
}
