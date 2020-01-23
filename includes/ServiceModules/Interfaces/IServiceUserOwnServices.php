<?php
namespace App\ServiceModules\Interfaces;

use App\Models\UserService;

/**
 * Obsługa wyświetlania użytkownikowi jego usług
 */
interface IServiceUserOwnServices
{
    /**
     * Metoda powinna zwrócić informacje o usłudze użytkownika.
     * Są one następnie wyświetlane na stronie user_own_services
     *
     * @param UserService $userService
     * @param string $buttonEdit        String przycisku do edycji usługi
     * (jeżeli moduł ma mieć mozliwość edycji usług przez użytkownika,
     * musisz ten przycisk umieścić w informacjach o usłudze)
     *
     * @return string Informacje o usłudze
     */
    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit);
}
