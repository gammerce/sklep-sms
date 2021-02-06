<?php
namespace App\View\Pagination;

use App\System\Application;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaginationFactory
{
    private Application $app;
    private TranslationManager $translationManager;

    public function __construct(Application $app, TranslationManager $translationManager)
    {
        $this->app = $app;
        $this->translationManager = $translationManager;
    }

    /**
     * @param Request $request
     * @return Pagination
     */
    public function create(Request $request): Pagination
    {
        $lang = $this->translationManager->shop();
        return $this->app->makeWith(Pagination::class, compact("lang", "request"));
    }
}
