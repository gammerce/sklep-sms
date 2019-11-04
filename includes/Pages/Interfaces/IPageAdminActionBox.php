<?php
namespace App\Pages\Interfaces;

interface IPageAdminActionBox
{
    /**
     * Zwraca html action boxa
     *
     * @param       $boxId
     * @param array $query
     *
     * @return string|null mixed
     */
    public function getActionBox($boxId, array $query);
}
