<?php
namespace App\ServiceModules;

use App\Models\Service;
use App\Models\UserService;
use App\Theme\Template;

abstract class ServiceModule
{
    /**
     * Module identifier defined by inheriting class
     */
    const MODULE_ID = "";

    /**
     * Database table where user services are stored
     */
    const USER_SERVICE_TABLE = "";

    public ?Service $service;
    protected Template $template;

    public function __construct(Template $template, ?Service $service = null)
    {
        $this->service = $service;
        $this->template = $template;
    }

    public function mapToUserService(array $data): UserService
    {
        return new UserService(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["user_id"]),
            as_int($data["expire"]),
            as_string($data["comment"])
        );
    }

    /**
     * Metoda wywoływana, gdy usługa jest usuwana.
     *
     * @param int $serviceId ID usługi
     */
    public function serviceDelete($serviceId): void
    {
        //
    }

    /**
     * Metoda wywoływana przy usuwaniu usługi użytkownika.
     *
     * @param UserService  $userService
     * @param string $who Kto wywołał akcję ( admin, task )
     *
     * @return bool
     */
    public function userServiceDelete(UserService $userService, $who): bool
    {
        return true;
    }

    /**
     * Metoda wywoływana po usunięciu usługi użytkownika.
     *
     * @param UserService $userService
     */
    public function userServiceDeletePost(UserService $userService): void
    {
        //
    }

    /**
     * Metoda powinna zwrócić, czy usługa ma być wyświetlana na stronie WWW.
     */
    public function showOnWeb(): bool
    {
        if ($this->service !== null) {
            return (bool) array_get($this->service->getData(), "web", false);
        }

        return false;
    }

    /**
     * Super krotki opis to 28 znakow, przeznaczony jest tylko na serwery
     * Krotki opis, to "description", krótki na strone WEB
     * Pełny opis, to plik z opisem całej usługi
     *
     * @return string    Description
     */
    public function descriptionLongGet(): string
    {
        return $this->template->render(
            "shop/services/{$this->service->getId()}_desc",
            [],
            true,
            false
        );
    }

    public function descriptionShortGet(): string
    {
        return $this->service->getDescriptionI18n();
    }

    public function getModuleId(): string
    {
        return $this::MODULE_ID;
    }

    public function getUserServiceTable(): string
    {
        return $this::USER_SERVICE_TABLE;
    }
}
