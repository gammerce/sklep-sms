<?php
namespace App\View\Html;

class ServerRef extends Link
{
    public function __construct($id, $name)
    {
        parent::__construct("$name ($id)", url("/admin/servers", ["record" => $id]));
    }
}
