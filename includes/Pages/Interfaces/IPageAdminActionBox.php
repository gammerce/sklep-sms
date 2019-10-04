<?php
namespace App\Pages\Interfaces;

interface IPageAdminActionBox
{
    /**
     * Zwraca html action boxa
     *
     * @param       $boxId
     * @param array $body Dane $_POST
     *
     * @return string|null mixed
     */
    public function getActionBox($boxId, $body);
}
