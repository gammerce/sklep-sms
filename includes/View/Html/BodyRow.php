<?php
namespace App\View\Html;

use App\Support\Template;
use App\Translation\TranslationManager;

class BodyRow extends Row
{
    /** @var string */
    private $dbId = null;

    /** @var I_ToHtml[] */
    private $actions = [];

    /** @var bool */
    private $editAction = false;

    /** @var bool */
    private $deleteAction = false;

    public function toHtml()
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
        // TODO Refactor usage
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
        $cell->setParam('headers', 'id');

        return $this->addCell($cell);
    }

    /** @return string */
    public function getDbId()
    {
        return $this->dbId;
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
            $editAction = new Link();
            $editAction->addClass("dropdown-item edit_row");
            $editAction->addContent($lang->t('edit'));
            $actions->addContent($editAction);
        }

        if ($this->deleteAction) {
            $deleteAction = new Link();
            $deleteAction->addClass("dropdown-item delete_row has-text-danger");
            $deleteAction->addContent($lang->t('delete'));
            $actions->addContent($deleteAction);
        }

        if ($actions->isEmpty()) {
            return null;
        }

        return new Cell(
            new UnescapedSimpleText($template->render("more_actions", compact("actions")))
        );
    }
}
