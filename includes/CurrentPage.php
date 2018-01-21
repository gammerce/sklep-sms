<?php
namespace App;

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

    public function __construct()
    {
        $this->pageNumber = isset($_GET['page']) && intval($_GET['page']) >= 1 ? intval($_GET['page']) : 1;
        $this->pid = isset($_GET['pid']) ? $_GET['pid'] : "home";
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
}