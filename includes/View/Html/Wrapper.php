<?php
namespace App\View\Html;

use App\Support\Template;
use Symfony\Component\HttpFoundation\Request;

class Wrapper extends Div
{
    protected ?Structure $table;
    protected ?string $title;
    protected bool $search = false;

    /** @var DOMElement[] */
    protected array $buttons = [];

    public function __construct()
    {
        parent::__construct();
        $this->addClass("table-structure");
    }

    public function toHtml(): string
    {
        /** @var Template $template */
        $template = app()->make(Template::class);

        /** @var Request $request */
        $request = app()->make(Request::class);

        $oldContent = $this->contents;

        $buttons = new Div();

        if ($this->search) {
            $searchText = $request->get("search");
            $buttons->addContent(
                new RawHtml($template->render("admin/form_search", compact("searchText")))
            );
        }

        foreach ($this->buttons as $button) {
            $buttons->addContent($button);
            $buttons->addContent(" ");
        }

        $pageTitle = $template->render("admin/page_title", [
            "buttons" => $buttons,
            "title" => $this->getTitle(),
        ]);

        $this->addContent(new RawHtml($pageTitle));
        $this->addContent($this->getTableContainer());

        $output = parent::toHtml();
        $this->contents = $oldContent;

        return $output;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle($title): self
    {
        $this->title = $title;
        return $this;
    }

    public function addButton(DOMElement $element): self
    {
        $this->buttons[] = $element;
        return $this;
    }

    public function enableSearch(): self
    {
        $this->search = true;
        return $this;
    }

    public function getTable(): ?Structure
    {
        return $this->table;
    }

    public function setTable(Structure $table): self
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
