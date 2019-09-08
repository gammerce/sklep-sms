<?php
namespace Admin\Table;

use App\CurrentPage;
use App\Routes\UrlGenerator;
use App\Template;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

interface I_ToHtml
{
    /**
     * Tworzy kod html elementu
     *
     * @return string
     */
    public function toHtml();
}

class SimpleText implements I_ToHtml
{
    /** @var  string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = strval($text);
    }

    /**
     * Tworzy kod html elementu
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->text;
    }
}

class DOMElement implements I_ToHtml
{
    /** @var  string */
    protected $name;

    /** @var  I_ToHtml[] */
    protected $contents = [];

    /** @var  array */
    protected $params;

    /**
     * @param string|null $value
     */
    public function __construct($value = null)
    {
        if ($value !== null) {
            $this->addContent(new SimpleText($value));
        }

        return $this;
    }

    public function toHtml()
    {
        $oldParams = $this->params;

        $style = [];
        foreach ((array) $this->getParam('style') as $key => $value) {
            if (!strlen($value)) {
                continue;
            }

            $style[] = htmlspecialchars($key) . ': ' . htmlspecialchars($value);
        }
        if (!empty($style)) {
            $this->setParam('style', implode('; ', $style));
        }

        $params = [];
        foreach ($this->params as $key => $value) {
            if (!strlen($value)) {
                continue;
            }

            $params[] = htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        $params = implode(' ', $params);

        $output = "<{$this->getName(true)} {$params}>";

        if (!in_array($this->getName(), ['input', 'img', 'br', 'hr'])) {
            foreach ($this->contents as $element) {
                $output .= $element->toHtml();
            }

            $output .= "</{$this->getName(true)}>";
        }

        $this->params = $oldParams;

        return $output;
    }

    /**
     * @param I_ToHtml $element
     */
    public function addContent($element)
    {
        $this->contents[] = $element;
    }

    /**
     * @param I_ToHtml $element
     */
    public function preaddContent($element)
    {
        $this->contents = array_merge([$element], $this->contents);
    }

    /**
     * @param string   $key
     * @param I_ToHtml $element
     */
    public function setContent($key, $element)
    {
        $this->contents[$key] = $element;
    }

    /**
     * @param string   $key
     * @param I_ToHtml $element
     */
    public function presetContent($key, $element)
    {
        unset($this->contents[$key]);
        $this->contents = array_merge([$key => $element], $this->contents);
    }

    /**
     * @param string $key
     *
     * @return I_ToHtml
     */
    public function getContent($key)
    {
        return $this->contents[$key];
    }

    /**
     * @return int
     */
    public function getContentsAmount()
    {
        return count($this->contents);
    }

    /**
     * @param $key
     *
     * @return string|array
     */
    public function getParam($key)
    {
        return if_isset($this->params[$key], '');
    }

    /**
     * @param string       $key
     * @param string|array $value
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setStyle($key, $value)
    {
        $this->params['style'][$key] = strval($value);
    }

    /**
     * @param bool $escape
     *
     * @return string
     */
    public function getName($escape = false)
    {
        return $escape ? htmlspecialchars($this->name) : $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = strval($name);
    }
}

class Input extends DOMElement
{
    protected $name = 'input';
}

class Select extends DOMElement
{
    protected $name = 'select';
}

class Option extends DOMElement
{
    protected $name = 'option';
}

class Div extends DOMElement
{
    protected $name = 'div';
}

class Img extends DOMElement
{
    protected $name = 'img';
}

class Row extends DOMElement
{
    protected $name = 'tr';
}

class Cell extends DOMElement
{
    protected $name = 'td';
}

class Line extends Row
{
    public function __construct()
    {
        $cell = new Cell();
        $cell->setParam('colspan', '31');
        $cell->setParam('class', 'line');

        $this->addContent($cell);
    }
}

class BodyRow extends Row
{
    /** @var string */
    private $dbId = null;

    /** @var I_ToHtml[] */
    private $actions = [];

    /** @var bool $buttonEdit */
    private $buttonEdit = false;

    /** @var bool $buttonDelete */
    private $buttonDelete = false;

    public function toHtml()
    {
        /** @var UrlGenerator $url */
        $url = app()->make(UrlGenerator::class);
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        // Zachowujemy poprzedni stan, aby go przywrocic
        $oldContents = $this->contents;

        $actions = new Cell();

        foreach ($this->actions as $action) {
            $actions->addContent($action);
        }

        if ($this->buttonEdit) {
            $button = new DOMElement();
            $button->setName('img');
            $button->setParam('class', "edit_row");
            $button->setParam('src', $url->to('images/edit.png'));
            $button->setParam('title', $lang->translate('edit') . ' ' . $this->dbId);
            $actions->addContent($button);
        }

        if ($this->buttonDelete) {
            $button = new DOMElement();
            $button->setName('img');
            $button->setParam('class', "delete_row");
            $button->setParam('src', $url->to('images/bin.png'));
            $button->setParam('title', $lang->translate('delete') . ' ' . $this->dbId);
            $actions->addContent($button);
        }

        $this->addContent($actions);

        $output = parent::toHtml();

        // Przywracamy poczatkowy stan
        $this->contents = $oldContents;

        return $output;
    }

    /**
     * @param DOMElement $cell
     */
    public function addCell($cell)
    {
        $this->addContent($cell);
    }

    /**
     * @param boolean $buttonEdit
     */
    public function setButtonEdit($buttonEdit = true)
    {
        $this->buttonEdit = (bool) $buttonEdit;
    }

    /**
     * @param boolean $buttonDelete
     */
    public function setButtonDelete($buttonDelete = true)
    {
        $this->buttonDelete = (bool) $buttonDelete;
    }

    /**
     * @param I_ToHtml $action
     */
    public function addAction($action)
    {
        $this->actions[] = $action;
    }

    /**
     * @param string $dbId
     */
    public function setDbId($dbId)
    {
        $this->dbId = strval($dbId);

        // Dodajemy kolumne z id
        $cell = new Cell($this->dbId);
        $cell->setParam('headers', 'id');
        $this->addCell($cell);
    }

    /**
     * @return string
     */
    public function getDbId()
    {
        return $this->dbId;
    }
}

class Structure extends DOMElement
{
    protected $name = 'table';

    /** @var DOMElement[] */
    private $headCells = [];

    /** @var BodyRow[] */
    private $bodyRows = [];

    /**
     * Ilość elementów w bazie danych
     * potrzebne do stworzenia paginacji
     *
     * @var int
     */
    private $dbRowsAmount;

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
        $actions = new Cell($lang->translate('actions'));
        $actions->setStyle('width', '4%');
        $headRow->addContent($actions);

        $head->addContent(new Line());
        $head->addContent($headRow);
        $head->addContent(new Line());

        // Tworzymy tbody
        $body = new DOMElement();
        $body->setName('tbody');
        foreach ($this->bodyRows as $row) {
            $body->addContent($row);
        }

        if (!$body->getContentsAmount()) {
            $row = new Row();
            $cell = new Cell($lang->translate('no_data'));
            $cell->setParam('colspan', '30');
            $cell->setStyle('text-align', 'center');
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
     */
    public function addHeadCell($headCell)
    {
        $this->headCells[] = $headCell;
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
     * @return int
     */
    public function getDbRowsAmount()
    {
        return $this->dbRowsAmount;
    }

    /**
     * @param int $amount
     */
    public function setDbRowsAmount($amount)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = app()->make(CurrentPage::class);
        /** @var Request $request */
        $request = app()->make(Request::class);

        $pageNumber = $currentPage->getPageNumber();
        $this->dbRowsAmount = intval($amount);

        $paginationTxt = get_pagination(
            $this->dbRowsAmount,
            $pageNumber,
            $request->getPathInfo(),
            $request->query->all()
        );
        if (strlen($paginationTxt)) {
            $this->foot = new DOMElement();
            $this->foot->setName('tfoot');
            $this->foot->setParam('class', 'display_tfoot');

            $row = new Row();

            $cell = new Cell($paginationTxt);
            $cell->setParam('colspan', '31');

            $row->addContent($cell);
            $this->foot->addContent($row);
        }
    }
}

class Wrapper extends Div
{
    /** @var  Structure */
    protected $table;

    /** @var  string */
    protected $title;

    /** @var  DOMElement[] */
    protected $buttons = [];

    /** @var bool */
    protected $search = false;

    public function __construct()
    {
        $this->setParam('class', 'table_structure');
    }

    public function toHtml()
    {
        /** @var Template $template */
        $template = app()->make(Template::class);

        /** @var Request $request */
        $request = app()->make(Request::class);

        $oldContets = $this->contents;

        $title = new Div();
        $title->setParam('class', 'title is-4');

        $buttons = new Div();
        $buttons->setStyle('float', 'right');

        if ($this->search) {
            $searchText = $request->get('search');
            $buttons->addContent(
                new SimpleText($template->render("admin/form_search", compact('searchText')))
            );
        }

        foreach ($this->buttons as $button) {
            $buttons->addContent($button);
            $buttons->addContent(new SimpleText(' '));
        }

        $title->addContent(new SimpleText($this->getTitle()));
        $title->addContent($buttons);
        $title->addContent(new SimpleText('<br class="clear" />'));

        $this->addContent($title);
        $this->addContent($this->getTable());

        $output = parent::toHtml();
        $this->contents = $oldContets;

        return $output;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param DOMElement $element
     */
    public function addButton($element)
    {
        $this->buttons[] = $element;
    }

    /**
     * @param bool $value
     */
    public function setSearch($value = true)
    {
        $this->search = $value;
    }

    /**
     * @return Structure
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param Structure $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }
}
