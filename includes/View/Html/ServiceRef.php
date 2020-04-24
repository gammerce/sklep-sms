<?php
namespace App\View\Html;

class ServiceRef extends Link
{
    public function __construct($id, $name)
    {
        parent::__construct("$name ($id)", url("/admin/services", ["record" => $id]));
    }
}
