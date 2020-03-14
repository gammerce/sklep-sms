<?php
namespace App\View\Html;

class Cell extends DOMElement
{
    protected $name = 'td';

    public function __construct($content = null, $headers = null)
    {
        parent::__construct($content);

        if ($headers) {
            $this->setParam('headers', $headers);
        }
    }
}
