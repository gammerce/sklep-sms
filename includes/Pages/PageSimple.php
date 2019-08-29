<?php
namespace App\Pages;

use Exception;

abstract class PageSimple extends Page
{
    protected $templateName = null;

    public function __construct()
    {
        if (!isset($this->templateName)) {
            throw new Exception(
                'Class ' .
                    get_class($this) .
                    ' has to have field $template because it extends class PageSimple'
            );
        }

        parent::__construct();
    }

    protected function content(array $query, array $body)
    {
        return $this->template->render($this->templateName);
    }
}
