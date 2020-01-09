<?php
namespace App\ServiceModules\Interfaces;

/**
 * Obsługa przejmowania usług przez użytkowników
 */
interface IServiceTakeOver
{
    /**
     * Zwraca formularz przejęcia usługi
     *
     * @return string
     */
    public function serviceTakeOverFormGet();

    /**
     * Sprawdza poprawność danych wprowadzonych w formularzu przejęcia usługi
     * a jeżeli wszystko jest ok, to ją przejmuje
     *
     * @param array $body
     *
     * @return array
     * status => id wiadomości
     * text => treść wiadomości
     * positive => czy udało się przejąć usługę
     */
    public function serviceTakeOver($body);
}
