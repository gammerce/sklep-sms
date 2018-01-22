<?php

namespace Admin\Table;

use App\CurrentPage;
use App\Template;
use App\Translator;

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
    function __construct($text)
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
    function __construct($value = null)
    {
        if ($value !== null) {
            $this->addContent(new SimpleText($value));
        }

        return $this;
    }

    public function toHtml()
    {
        $old_params = $this->params;

        $style = [];
        foreach ((array)$this->getParam('style') as $key => $value) {
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

        $this->params = $old_params;

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
    function __construct()
    {
        $cell = new Cell();
        $cell->setParam('colspan', '31');
        $cell->setParam('class', 'line');

        $this->addContent($cell);
    }
}

class BodyRow extends Row
{
    /** @var  string */
    private $db_id = null;

    /** @var  I_ToHtml[] */
    private $actions = [];

    /** @var bool $button_edit */
    private $button_edit = false;

    /** @var bool $button_delete */
    private $button_delete = false;

    public function toHtml()
    {
        /** @var Translator $lang */
        $lang = app()->make(Translator::class);

        // Zachowujemy poprzedni stan, aby go przywrocic
        $old_contents = $this->contents;

        $actions = new Cell();

        foreach ($this->actions as $action) {
            $actions->addContent($action);
        }

        if ($this->button_edit) {
            $button = new DOMElement();
            $button->setName('img');
            $button->setParam('class', "edit_row");
            $button->setParam('src', 'images/edit.png');
            $button->setParam('title', $lang->translate('edit') . ' ' . $this->db_id);
            $actions->addContent($button);
        }

        if ($this->button_delete) {
            $button = new DOMElement();
            $button->setName('img');
            $button->setParam('class', "delete_row");
            $button->setParam('src', 'images/bin.png');
            $button->setParam('title', $lang->translate('delete') . ' ' . $this->db_id);
            $actions->addContent($button);
        }

        $this->addContent($actions);

        $output = parent::toHtml();

        // Przywracamy poczatkowy stan
        $this->contents = $old_contents;

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
     * @param boolean $button_edit
     */
    public function setButtonEdit($button_edit = true)
    {
        $this->button_edit = (bool)$button_edit;
    }

    /**
     * @param boolean $button_delete
     */
    public function setButtonDelete($button_delete = true)
    {
        $this->button_delete = (bool)$button_delete;
    }

    /**
     * @param I_ToHtml $action
     */
    public function addAction($action)
    {
        $this->actions[] = $action;
    }

    /**
     * @param string $db_id
     */
    public function setDbId($db_id)
    {
        $this->db_id = strval($db_id);

        // Dodajemy kolumne z id
        $cell = new Cell($this->db_id);
        $cell->setParam('headers', 'id');
        $this->addCell($cell);
    }

    /**
     * @return string
     */
    public function getDbId()
    {
        return $this->db_id;
    }
}

class Structure extends DOMElement
{
    protected $name = 'table';

    /** @var  DOMElement[] */
    private $head_cells;

    /** @var  BodyRow[] */
    private $body_rows;

    /**
     * Ilość elementów w bazie danych
     * potrzebne do stworzenia paginacji
     *
     * @var int
     */
    private $db_rows_amount;

    /** @var  DOMElement */
    public $foot = null;

    public function toHtml()
    {
        /** @var Translator $lang */
        $lang = app()->make(Translator::class);

        // Tworzymy thead
        $head = new DOMElement();
        $head->setName('thead');

        $head_row = new Row();
        foreach ($this->head_cells as $cell) {
            $head_row->addContent($cell);
        }
        $actions = new Cell($lang->translate('actions'));
        $actions->setStyle('width', '4%');
        $head_row->addContent($actions);

        $head->addContent(new Line());
        $head->addContent($head_row);
        $head->addContent(new Line());

        // Tworzymy tbody
        $body = new DOMElement();
        $body->setName('tbody');
        foreach ($this->body_rows as $row) {
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
     * @param DOMElement $head_cell
     */
    public function addHeadCell($head_cell)
    {
        $this->head_cells[] = $head_cell;
    }

    /**
     * @param string     $key
     * @param DOMElement $head_cell
     */
    public function setHeadCell($key, $head_cell)
    {
        $this->head_cells[$key] = $head_cell;
    }

    /**
     * @param BodyRow $body_row
     */
    public function addBodyRow($body_row)
    {
        $this->body_rows[] = $body_row;
    }

    /**
     * @return int
     */
    public function getDbRowsAmount()
    {
        return $this->db_rows_amount;
    }

    /**
     * @param int $amount
     */
    public function setDbRowsAmount($amount)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = app()->make(CurrentPage::class);

        $pageNumber = $currentPage->getPageNumber();
        $this->db_rows_amount = intval($amount);

        $pagination_txt = get_pagination($this->db_rows_amount, $pageNumber, "admin.php", $_GET);
        if (strlen($pagination_txt)) {
            $this->foot = new DOMElement();
            $this->foot->setName('tfoot');
            $this->foot->setParam('class', 'display_tfoot');

            $row = new Row();

            $cell = new Cell($pagination_txt);
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
    protected $buttons;

    /** @var bool */
    protected $search = false;

    function __construct()
    {
        $this->setParam('class', 'table_structure');
    }

    public function toHtml()
    {
        /** @var Template $template */
        $template = app()->make(Template::class);

        /** @var Translator $lang */
        $lang = app()->make(Translator::class);

        $old_contets = $this->contents;

        $title = new Div();
        $title->setParam('class', 'title');

        $buttons = new Div();
        $buttons->setStyle('float', 'right');

        if ($this->search) {
            $search_text = $_GET['search'];
            $buttons->addContent(new SimpleText(eval($template->render("admin/form_search"))));
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
        $this->contents = $old_contets;

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