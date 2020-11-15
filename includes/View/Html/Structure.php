<?php
namespace App\View\Html;

use App\Translation\TranslationManager;
use App\View\Pagination\Pagination;

class Structure extends DOMElement
{
    /** @var DOMElement[] */
    private $headCells = [];

    /** @var BodyRow[] */
    private $bodyRows = [];

    /** @var DOMElement */
    public $foot = null;

    public function __construct($content = null)
    {
        parent::__construct("table", $content);
        $this->addClass("table is-fullwidth is-hoverable");
    }

    public function toHtml()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        $hasActions = collect($this->bodyRows)->some(function (BodyRow $bodyRow) {
            return $bodyRow->hasAnyAction();
        });

        // THEAD
        $head = new DOMElement("thead");

        $headRow = new Row();
        foreach ($this->headCells as $cell) {
            $headRow->addContent($cell);
        }

        if ($hasActions) {
            $actions = new HeadCell($lang->t("actions"));
            $actions->setStyle("width", "4%");
            $headRow->addContent($actions);
        }

        $head->addContent($headRow);

        // TBODY
        $body = new DOMElement("tbody");
        foreach ($this->bodyRows as $row) {
            $body->addContent($row);
        }

        if ($body->isEmpty()) {
            $row = new Row();
            $cell = new Cell($lang->t("no_data"));
            $cell->setParam("colspan", "30");
            $cell->addClass("has-text-centered");
            $cell->setStyle("padding", "40px");
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
     * @param Pagination $pagination
     * @param int $count
     * @return $this
     */
    public function enablePagination($path, Pagination $pagination, $count)
    {
        $paginationView = $pagination->createView($count, $path);

        if ($paginationView) {
            $cell = new Cell($paginationView);
            $cell->setParam("colspan", "31");

            $row = new Row($cell);

            $this->foot = (new DOMElement("tfoot", $row))->addClass("display_tfoot");
        }

        return $this;
    }
}
