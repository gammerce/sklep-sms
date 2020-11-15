<?php
namespace App\View\Pagination;

use App\System\Application;
use Symfony\Component\HttpFoundation\Request;

class PaginationFactory
{
    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param Request $request
     * @return Pagination
     */
    public function create(Request $request)
    {
        return $this->app->makeWith(Pagination::class, compact("request"));
    }
}
