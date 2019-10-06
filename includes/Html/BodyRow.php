<?php
namespace App\Html;

use App\Template;
use App\TranslationManager;

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
     */
    public function addCell($cell)
    {
        $this->addContent($cell);
    }

    /**
     * @param boolean $editAction
     */
    public function setEditAction($editAction = true)
    {
        $this->editAction = (bool) $editAction;
    }

    /**
     * @param boolean $deleteAction
     */
    public function setDeleteAction($deleteAction = true)
    {
        $this->deleteAction = (bool) $deleteAction;
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
            $editAction->setParam('class', "dropdown-item edit_row");
            $editAction->addContent(new SimpleText($lang->translate('edit')));
            $actions->addContent($editAction);
        }

        if ($this->deleteAction) {
            $deleteAction = new Link();
            $deleteAction->setParam('class', "dropdown-item delete_row has-text-danger");
            $deleteAction->addContent(new SimpleText($lang->translate('delete')));
            $actions->addContent($deleteAction);
        }

        if ($actions->isEmpty()) {
            return null;
        }

        return new Cell($template->render("more_actions", compact("actions")));
    }
}
