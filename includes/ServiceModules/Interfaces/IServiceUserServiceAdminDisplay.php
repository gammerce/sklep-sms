<?php
namespace App\ServiceModules\Interfaces;

use App\Html\Wrapper;

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
     * @param array $query
     * @param array $body
     *
     * @return Wrapper | string
     */
    public function userServiceAdminDisplayGet(array $query, array $body);
}
