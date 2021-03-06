<?php
namespace App\View\Html;

use App\Theme\Template;
use App\Translation\TranslationManager;

class BodyRow extends Row
{
    /** @var string */
    private $dbId = null;

    /** @var I_ToHtml[] */
    private array $actions = [];

    private bool $editAction = false;
    private bool $deleteAction = false;

    public function toHtml(): string
    {
        // Zachowujemy poprzedni stan, aby go przywrocic
        $oldContents = $this->contents;

        $actions = $this->renderActions();
        if ($actions) {
            $this->addContent($actions);
        }

        $output = parent::toHtml();

        // Przywracamy poczatkowy stan
        $this->contents = $oldContents;

        return $output;
    }

    /**
     * @param DOMElement $cell
     * @return $this
     */
    public function addCell($cell)
    {
        return $this->addContent($cell);
    }

    /**
     * @param boolean $editAction
     * @return $this
     */
    public function setEditAction($editAction = true)
    {
        $this->editAction = (bool) $editAction;
        return $this;
    }

    /**
     * @param boolean $deleteAction
     * @return $this
     */
    public function setDeleteAction($deleteAction = true)
    {
        $this->deleteAction = (bool) $deleteAction;
        return $this;
    }

    /**
     * @param I_ToHtml $action
     * @return $this
     */
    public function addAction($action)
    {
        $this->actions[] = $action;
        return $this;
    }

    /**
     * @param string $dbId
     * @return $this
     */
    public function setDbId($dbId)
    {
        $this->dbId = (string) $dbId;

        $cell = new Cell($this->dbId);
        $cell->setParam("headers", "id");

        return $this->addCell($cell);
    }

    /** @return string */
    public function getDbId()
    {
        return $this->dbId;
    }

    /**
     * @return bool
     */
    public function hasAnyAction()
    {
        return $this->deleteAction || $this->editAction || !empty($this->actions);
    }

    private function renderActions()
    {
        /** @var Template $template */
        $template = app()->make(Template::class);

        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        $actions = new Div();

        foreach ($this->actions as $action) {
            $actions->addContent($action);
        }

        if ($this->editAction) {
            $editAction = (new Link($lang->t("edit")))->addClass("dropdown-item edit_row");
            $actions->addContent($editAction);
        }

        if ($this->deleteAction) {
            $deleteAction = (new Link($lang->t("delete")))->addClass(
                "dropdown-item delete_row has-text-danger"
            );
            $actions->addContent($deleteAction);
        }

        if ($actions->isEmpty()) {
            return null;
        }

        return new Cell(
            new RawHtml($template->render("admin/more_actions", compact("actions"))),
            "actions"
        );
    }
}
