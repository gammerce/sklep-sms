<?php
namespace App\Pages\Interfaces;

interface IPageAdminActionBox
{
    /**
     * Zwraca html action boxa
     *
     * @param       $boxId
     * @param array $post Dane $_POST
     *
     * @return string|null mixed
     */
    public function getActionBox($boxId, $post);
}
