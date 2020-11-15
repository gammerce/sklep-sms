<?php
namespace App\View\Pagination;

use App\System\Application;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaginationFactory
{
    /** @var Application */
    private $app;

    /** @var TranslationManager */
    private $translationManager;

    public function __construct(Application $app, TranslationManager $translationManager)
    {
        $this->app = $app;
        $this->translationManager = $translationManager;
    }

    /**
     * @param Request $request
     * @return Pagination
     */
    public function create(Request $request)
    {
        $lang = $this->translationManager->shop();
        return $this->app->makeWith(Pagination::class, compact("lang", "request"));
    }
}
