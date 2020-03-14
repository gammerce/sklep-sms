<?php
namespace App\View\Html;

use App\Support\Template;
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
                new UnescapedSimpleText(
                    $template->render("admin/form_search", compact('searchText'))
                )
            );
        }

        foreach ($this->buttons as $button) {
            $buttons->addContent($button);
            $buttons->addContent(' ');
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
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param DOMElement $element
     * @return $this
     */
    public function addButton($element)
    {
        $this->buttons[] = $element;
        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setSearch($value = true)
    {
        $this->search = $value;
        return $this;
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
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
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
