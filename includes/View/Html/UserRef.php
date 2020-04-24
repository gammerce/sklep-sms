<?php
namespace App\View\Html;

class UserRef extends Link
{
    public function __construct($id, $name)
    {
        parent::__construct("$name ($id)", url("/admin/users", ["record" => $id]));
    }
}
