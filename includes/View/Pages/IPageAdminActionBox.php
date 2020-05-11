<?php
namespace App\View\Pages;

// TODO getActionBox return value should be a string. In case of an error exception should be thrown

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
