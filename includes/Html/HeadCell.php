<?php
namespace App\Html;

class HeadCell extends DOMElement
{
    protected $name = 'th';

    public function __construct($value = null, $headers = null)
    {
        parent::__construct($value);

        if ($headers) {
            $this->setParam('headers', $headers);
        }
    }
}
