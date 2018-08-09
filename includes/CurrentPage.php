<?php
namespace App;

use Symfony\Component\HttpFoundation\Request;

class CurrentPage
{
    /**
     * Page number
     *
     * @var int
     */
    protected $pageNumber;

    /**
     * Page ID
     *
     * @var string
     */
    protected $pid;

    public function __construct(Request $request)
    {
        $this->pageNumber = $this->resolvePageNumber($request);
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

    protected function resolvePageNumber(Request $request)
    {
        $pageNumber = intval($request->get('page', 1));

        return max($pageNumber, 1);
    }

    protected function resolvePid(Request $request)
    {
        return $request->get('pid', 'home');
    }
}
