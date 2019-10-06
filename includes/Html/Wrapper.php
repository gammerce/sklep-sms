<?php
namespace App\Html;

use App\Template;
use Symfony\Component\HttpFoundation\Request;

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
        parent::__construct();
        $this->addClass('table-structure');
    }

    public function toHtml()
    {
        /** @var Template $template */
        $template = app()->make(Template::class);

        /** @var Request $request */
        $request = app()->make(Request::class);

        $oldContent = $this->contents;

        $buttons = new Div();

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

        $title = new Div($this->getTitle());
        $title->addClass("title is-4");

        $pageTitle = new Div();
        $pageTitle->addClass('page-title');
        $pageTitle->addContent($title);
        $pageTitle->addContent($buttons);

        $this->addContent($pageTitle);
        $this->addContent($this->getTableContainer());

        $output = parent::toHtml();
        $this->contents = $oldContent;

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

    /**
     * @return DOMElement
     */
    public function getTableContainer()
    {
        $container = new Div();
        $container->addClass("table-container");
        $container->addContent($this->getTable());
        return $container;
    }
}
