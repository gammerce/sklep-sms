<?php
namespace App\View;

use Symfony\Component\HttpFoundation\Request;

class CurrentPage
{
    /**
     * Page number
     *
     * @var int
     */
    private $pageNumber;

    public function __construct(Request $request)
    {
        $this->pageNumber = $this->resolvePageNumber($request);
    }

    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    private function resolvePageNumber(Request $request)
    {
        $pageNumber = (int) $request->get('page', 1);
        return max($pageNumber, 1);
    }
}
