<?php
namespace App\ServiceModules;

use App\Models\Service;
use App\Models\UserService;
use App\Services\ServiceDescriptionService;
use App\Support\Database;
use App\Support\Template;
use App\System\Application;

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

    /** @var Service|null */
    public $service;

    /** @var Application */
    protected $app;

    /** @var Template */
    protected $template;

    /** @var Database */
    protected $db;

    /** @var ServiceDescriptionService */
    protected $serviceDescriptionService;

    public function __construct(Service $service = null)
    {
        $this->service = $service;
        $this->app = app();
        $this->template = $this->app->make(Template::class);
        $this->db = $this->app->make(Database::class);
        $this->serviceDescriptionService = $this->app->make(ServiceDescriptionService::class);
    }

    /**
     * @param array $data
     * @return UserService
     */
    public function mapToUserService(array $data)
    {
        return new UserService(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["uid"]),
            as_int($data["expire"])
        );
    }

    /**
     * Metoda wywoływana, gdy usługa jest usuwana.
     *
     * @param int $serviceId ID usługi
     */
    public function serviceDelete($serviceId)
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
    public function userServiceDelete(UserService $userService, $who)
    {
        return true;
    }

    /**
     * Metoda wywoływana po usunięciu usługi użytkownika.
     *
     * @param UserService $userService
     */
    public function userServiceDeletePost(UserService $userService)
    {
        //
    }

    /**
     * Metoda powinna zwrócić, czy usługa ma być wyświetlana na stronie WWW.
     */
    public function showOnWeb()
    {
        if ($this->service !== null) {
            return array_get($this->service->getData(), "web", false);
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
    public function descriptionLongGet()
    {
        $templatePath = $this->serviceDescriptionService->getTemplatePath($this->service->getId());
        return $this->template->render($templatePath, [], true, false);
    }

    public function descriptionShortGet()
    {
        return $this->service->getDescriptionI18n();
    }

    public function getModuleId()
    {
        return $this::MODULE_ID;
    }

    protected function getUserServiceTable()
    {
        return $this::USER_SERVICE_TABLE;
    }
}
