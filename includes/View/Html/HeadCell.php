<?php
namespace App\View\Html;

class HeadCell extends DOMElement
{
    protected $name = 'th';

    public function __construct($content = null, $headers = null)
    {
        parent::__construct($content);

        if ($headers) {
            $this->setParam('headers', $headers);
        }
    }
}
