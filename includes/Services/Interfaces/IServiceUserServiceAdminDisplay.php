<?php
namespace App\Services\Interfaces;

use Admin\Table\Wrapper;

/**
 * Obsługa wyświetlania trwających usług użytkowników w PA
 * (Ten interfejs powinien być implementowany w klasie *Simple modułu usługi)
 */
interface IServiceUserServiceAdminDisplay
{
    /**
     * Zwraca tytuł strony, gdy włączona jest lista usług użytkowników
     *
     * @return string
     */
    public function userServiceAdminDisplayTitleGet();

    /**
     * Zwraca listę usług użytkowników ubraną w ładny obiekt.
     *
     * @param array $get
     * @param array $post
     *
     * @return Wrapper | string
     */
    public function userServiceAdminDisplayGet($get, $post);
}
