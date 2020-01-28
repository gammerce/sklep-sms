<?php
namespace App\ServiceModules\Interfaces;

use App\Http\Validation\Validator;

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
     *
     * @param Validator $validator
     */
    public function serviceAdminManagePre(Validator $validator);

    /**
     * Metoda zostaje wywołana po tym, jak  weryfikacja danych
     * przesłanych w formularzu dodania nowej usługi w PA przebiegła bezproblemowo
     *
     * @param array $body
     * @return array
     */
    public function serviceAdminManagePost(array $body);
}
