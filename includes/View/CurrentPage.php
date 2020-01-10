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

    /**
     * Page ID
     *
     * @var string
     */
    private $pid;

    public function __construct(Request $request)
    {
        $this->pageNumber = $this->resolvePageNumber($request);
        // Some API requests pass pid. That's why we resolve it
        $this->pid = $this->resolvePid($request);
    }

    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;
    }

    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    public function getPid()
    {
        return $this->pid;
    }

    private function resolvePageNumber(Request $request)
    {
        $pageNumber = (int) $request->get('page', 1);
        return max($pageNumber, 1);
    }

    private function resolvePid(Request $request)
    {
        return $request->get('pid', 'home');
    }
}
