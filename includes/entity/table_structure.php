<?php

namespace Admin\Table;

class Wrapper extends Div
{

	/** @var  Structure */
	public $table;

	/** @var  string */
	private $title;

	/** @var  DOMElement[] */
	private $buttons;

	/** @var bool */
	private $search = false;

	function __construct()
	{
		$this->setParam('class', 'table_structure');
	}

	public function toHtml() {
		global $templates;

		$old_contets = $this->contents;

		$title = new Div();
		$title->setParam('class', 'title');

		$buttons = new Div();
		$buttons->setStyle('float', 'right');
		foreach ($this->buttons as $button) {
			$buttons->addContent($button);
		}
		$search_text = $_GET['search'];
		$buttons->addContent(new String(eval($templates->render("admin/form_search"))));

		$title->addContent(new String($this->getTitle()));
		$title->addContent($buttons);
		$title->addContent(new String('<br class="clear" />'));

		$this->addContent($title);
		$this->addContent($this->table);

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
	public $foot = NULL;

	public function toHtml()
	{
		global $lang;

		// Tworzymy thead
		$head = new DOMElement();
		$head->setName('thead');

		$row = new Row();
		foreach ($this->head_cells as $cell) {
			$row->addContent($cell);
		}
		$actions = new Cell($lang->actions);
		$actions->setStyle('width', '4%');
		$row->addContent($actions);

		$head->addContent(new Line());
		$head->addContent($row);
		$head->addContent(new Line());

		// Tworzymy tbody
		$body = new DOMElement();
		$body->setName('tbody');
		foreach ($this->body_rows as $row) {
			$body->addContent($row);
		}

		if ($body->getContentsAmount()) {
			$row = new Row();
			$cell = new Cell($lang->no_data);
			$cell->setParam('colspan', '30');
			$cell->setStyle('text-align', 'center');
			$cell->setStyle('padding', '40px');
			$row->addContent($cell);
			$body->addContent($row);
		}

		$this->contents = array();
		$this->addContent($head);
		$this->addContent($body);
		if ($this->foot !== NULL)
			$this->addContent($this->foot);

		return parent::toHtml();
	}

	/**
	 * @param DOMElement $head_cell
	 */
	public function addHeadCell($head_cell)
	{
		$head_cell->setName('td');
		$this->head_cells[] = $head_cell;
	}

	/**
	 * @param string $key
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
		$body_row->setId(count($this->body_rows) + 1);
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
		global $G_PAGE;

		$this->db_rows_amount = intval($amount);

		$pagination_txt = get_pagination($this->db_rows_amount, $G_PAGE, "admin.php", $_GET);
		if (strlen($pagination_txt)) {
			$this->foot = new DOMElement();
			$this->foot->setName('tfoot');

			$row = new Row();

			$cell = new Cell($pagination_txt);
			$cell->setParam('colspan', '31');

			$row->addContent($cell);
			$this->foot->addContent($row);
		}
	}

}

class BodyRow extends Row
{

	/** @var  string */
	private $id;

	/** @var  string */
	private $db_id;

	/** @var  DOMElement[] */
	private $actions;

	/** @var bool $button_edit */
	private $button_edit = false;

	/** @var bool $button_delete */
	private $button_delete = false;

	public function toHtml()
	{
		global $lang;

		// Zachowujemy poprzedni stan, aby go przywrocic
		$old_contents = $this->contents;

		$actions = new Cell();

		foreach ($this->actions as $action) {
			$actions->addContent($action);
		}

		if ($this->button_edit) {
			$button = new DOMElement();
			$button->setName('img');
			$button->setParam('id', "edit_row_{$this->getId(true)}");
			$button->setParam('src', 'images/bin.png');
			$button->setParam('title', $lang->edit . ' ' . $this->db_id);
			$actions->addContent($button);
		}

		if ($this->button_delete) {
			$button = new DOMElement();
			$button->setName('img');
			$button->setParam('id', "delete_row_{$this->getId(true)}");
			$button->setParam('src', 'images/bin.png');
			$button->setParam('title', $lang->delete . ' ' . $this->db_id);
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
		$this->button_edit = boolval($button_edit);
	}

	/**
	 * @param boolean $button_delete
	 */
	public function setButtonDelete($button_delete = true)
	{
		$this->button_delete = boolval($button_delete);
	}

	/**
	 * @param DOMElement $action
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
	}

	/**
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = strval($id);
	}

	/**
	 * @param bool $escape
	 * @return string
	 */
	public function getId($escape = false)
	{
		return $escape ? htmlspecialchars($this->id) : $this->id;
	}

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

class Row extends DOMElement
{
	protected $name = 'tr';
}

class Cell extends DOMElement
{
	protected $name = 'td';
}

class DOMElement implements I_ToHtml
{

	/** @var  string */
	protected $name;

	/** @var  I_ToHtml[] */
	protected $contents;

	/** @var  array */
	protected $params;

	/**
	 * @param string $value
	 */
	function __construct($value = '')
	{
		$this->addContent(new String($value));
	}

	public function toHtml()
	{
		$old_params = $this->params;

		$style = array();
		foreach ((array)$this->getParam('style') as $key => $value) {
			if (!strlen($value))
				continue;

			$style[] = htmlspecialchars($key) . ': ' . htmlspecialchars($value);
		}
		if (!empty($style))
			$this->setParam('style', implode('; ', $style));

		$params = array();
		foreach ($this->params as $key => $value) {
			if (!strlen($value))
				continue;

			$params[] = htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
		}
		$params = implode(' ', $params);

		$output = "<{$this->getName(true)} {$params}>";

		if (!in_array($this->getName(), array('input', 'img', 'br', 'hr'))) {
			foreach ($this->contents as $element)
				$output .= "\n" . $element->toHtml();

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
	 * @param string $key
	 * @param I_ToHtml $element
	 */
	public function setContent($key, $element)
	{
		$this->contents[$key] = $element;
	}

	/**
	 * @param string $key
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
	 * @return string|array
	 */
	public function getParam($key)
	{
		return if_isset($this->params[$key], '');
	}

	/**
	 * @param string $key
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

class String implements I_ToHtml
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

interface I_ToHtml
{

	/**
	 * Tworzy kod html elementu
	 *
	 * @return string
	 */
	public function toHtml();

}