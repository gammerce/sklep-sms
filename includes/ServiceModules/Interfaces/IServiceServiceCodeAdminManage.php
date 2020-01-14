<?php
namespace App\ServiceModules\Interfaces;

/**
 * Obsługa dodawania nowych kodów na usługę w PA
 */
interface IServiceServiceCodeAdminManage
{
    // TODO Remove that method
    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania kodu na usługę
     *
     * @return string
     */
    public function serviceCodeAdminAddFormGet();
}
