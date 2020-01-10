<?php
namespace App\ServiceModules\Interfaces;

/**
 * Obsługa dodawania nowych usług w PA
 * (Ten interfejs powinien być implementowany w klasie *Simple modułu usługi)
 */
interface IServiceAdminManage
{
    /**
     * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
     * Powinna zwracać dodatkowe pola do uzupełnienia
     *
     * @return string
     */
    public function serviceAdminExtraFieldsGet();

    /**
     * Metoda testuje dane przesłane przez formularz podczas dodawania nowej usługi w PA
     * jak coś się jej nie spodoba to zwraca o tym info w tablicy
     *
     * @param array $body
     *
     * @return array
     *  'key' => DOM Element name
     *  'value' => Array of error messages
     */
    public function serviceAdminManagePre(array $body);

    /**
     * Metoda zostaje wywołana po tym, jak  weryfikacja danych
     * przesłanych w formularzu dodania nowej usługi w PA przebiegła bezproblemowo
     *
     * @param array $body
     *
     * @return array
     */
    public function serviceAdminManagePost(array $body);
}
