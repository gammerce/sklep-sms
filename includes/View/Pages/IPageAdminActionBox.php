<?php
namespace App\View\Pages;

interface IPageAdminActionBox
{
    /**
     * Returns HTML content of action box
     *
     * @param string $boxId
     * @param array $query
     *
     * @return array
     */
    public function getActionBox($boxId, array $query);
}
