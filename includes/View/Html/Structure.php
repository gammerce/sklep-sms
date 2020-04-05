<?php
namespace App\View\Html;

use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\PaginationService;

class Structure extends DOMElement
{
    protected $name = 'table';

    protected $params = [
        "class" => "table is-fullwidth is-hoverable",
    ];

    /** @var DOMElement[] */
    private $headCells = [];

    /** @var BodyRow[] */
    private $bodyRows = [];

    /** @var DOMElement */
    public $foot = null;

    public function toHtml()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        // Tworzymy thead
        $head = new DOMElement();
        $head->setName('thead');

        $headRow = new Row();
        foreach ($this->headCells as $cell) {
            $headRow->addContent($cell);
        }
        $actions = new HeadCell($lang->t('actions'));
        $actions->setStyle('width', '4%');
        $headRow->addContent($actions);

        $head->addContent($headRow);

        // Tworzymy tbody
        $body = new DOMElement();
        $body->setName('tbody');
        foreach ($this->bodyRows as $row) {
            $body->addContent($row);
        }

        if ($body->isEmpty()) {
            $row = new Row();
            $cell = new Cell($lang->t('no_data'));
            $cell->setParam('colspan', '30');
            $cell->addClass("has-text-centered");
            $cell->setStyle('padding', '40px');
            $row->addContent($cell);
            $body->addContent($row);
        }

        $this->contents = [];
        $this->addContent($head);
        $this->addContent($body);
        if ($this->foot !== null) {
            $this->addContent($this->foot);
        }

        return parent::toHtml();
    }

    /**
     * @param DOMElement $headCell
     * @return $this
     */
    public function addHeadCell($headCell)
    {
        $this->headCells[] = $headCell;
        return $this;
    }

    /**
     * @param string     $key
     * @param DOMElement $headCell
     */
    public function setHeadCell($key, $headCell)
    {
        $this->headCells[$key] = $headCell;
    }

    /**
     * @param BodyRow $bodyRow
     */
    public function addBodyRow($bodyRow)
    {
        $this->bodyRows[] = $bodyRow;
    }

    /**
     * @param BodyRow[] $bodyRows
     * @return $this
     */
    public function addBodyRows(array $bodyRows)
    {
        foreach ($bodyRows as $bodyRow) {
            $this->addBodyRow($bodyRow);
        }

        return $this;
    }

    /**
     * @param string $path
     * @param array $query
     * @param int $count
     * @return $this
     */
    public function enablePagination($path, array $query, $count)
    {
        /** @var PaginationService $paginationService */
        $paginationService = app()->make(PaginationService::class);

        /** @var CurrentPage $currentPage */
        $currentPage = app()->make(CurrentPage::class);

        $pagination = $paginationService->createPagination(
            $count,
            $currentPage->getPageNumber(),
            $path,
            $query
        );

        if ($pagination) {
            $cell = new Cell($pagination);
            $cell->setParam('colspan', '31');

            $row = new Row();
            $row->addContent($cell);

            $this->foot = (new DOMElement())
                ->setName('tfoot')
                ->addClass('display_tfoot')
                ->addContent($row);
        }

        return $this;
    }
}
