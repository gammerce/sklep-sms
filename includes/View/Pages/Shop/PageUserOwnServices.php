<?php
namespace App\View\Pages\Shop;

use App\Managers\ServiceModuleManager;
use App\Models\UserService;
use App\Service\UserServiceService;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\ServiceModules\ServiceModule;
use App\Support\Database;
use App\Support\Template;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageUserOwnServices extends Page implements IBeLoggedMust
{
    const PAGE_ID = "user_own_services";

    private UserServiceService $userServiceService;
    private Settings $settings;
    private Auth $auth;
    private Database $db;
    private PaginationFactory $paginationFactory;
    private ServiceModuleManager $serviceModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        UserServiceService $userServiceService,
        Settings $settings,
        Auth $auth,
        Database $db,
        ServiceModuleManager $serviceModuleManager,
        PaginationFactory $paginationFactory
    ) {
        parent::__construct($template, $translationManager);

        $this->userServiceService = $userServiceService;
        $this->settings = $settings;
        $this->auth = $auth;
        $this->db = $db;
        $this->paginationFactory = $paginationFactory;
        $this->serviceModuleManager = $serviceModuleManager;
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("user_own_services");
    }

    public function getContent(Request $request)
    {
        $user = $this->auth->user();
        $pagination = $this->paginationFactory->create($request);

        $moduleIds = collect($this->serviceModuleManager->all())
            ->filter(
                fn(ServiceModule $serviceModule) => $serviceModule instanceof
                    IServiceUserOwnServices
            )
            ->map(fn(ServiceModule $serviceModule) => $serviceModule->getModuleId());

        $usersServices = [];
        $rowsCount = 0;
        if ($moduleIds->isPopulated()) {
            $keys = $moduleIds->map(fn() => "?")->join(", ");

            $statement = $this->db->statement(
                "SELECT COUNT(*) FROM `ss_user_service` AS us " .
                    "INNER JOIN `ss_services` AS s ON us.service_id = s.id " .
                    "WHERE us.user_id = ? AND s.module IN ({$keys}) "
            );
            $statement->execute(array_merge([$user->getId()], $moduleIds->all()));
            $rowsCount = $statement->fetchColumn();

            $statement = $this->db->statement(
                <<<EOF
SELECT us.id FROM `ss_user_service` AS us 
INNER JOIN `ss_services` AS s ON us.service_id = s.id
WHERE us.user_id = ? AND s.module IN ({$keys})
ORDER BY us.id DESC
LIMIT ?, ?
EOF
            );
            $statement->execute(
                array_merge([$user->getId()], $moduleIds->all(), $pagination->getSqlLimit(4))
            );

            $userServiceIds = collect($statement)
                ->map(fn(array $row) => $row["id"])
                ->join(", ");

            if ($userServiceIds) {
                $usersServices = $this->userServiceService->find(
                    "WHERE us.id IN ({$userServiceIds})"
                );
            }
        }

        $userOwnServices = collect($usersServices)
            ->filter(function (UserService $userService) {
                $serviceModule = $this->serviceModuleManager->get($userService->getServiceId());
                return $serviceModule instanceof IServiceUserOwnServices;
            })
            ->map(function (UserService $userService) {
                /** @var IServiceUserOwnServices $serviceModule */
                $serviceModule = $this->serviceModuleManager->get($userService->getServiceId());

                if (
                    $this->settings["user_edit_service"] &&
                    $serviceModule instanceof IServiceUserOwnServicesEdit
                ) {
                    $buttonEdit = $this->template->render(
                        "shop/components/user_own_services/edit_button"
                    );
                } else {
                    $buttonEdit = "";
                }

                $content = $serviceModule->userOwnServiceInfoGet($userService, $buttonEdit);

                return $this->template->render(
                    "shop/components/user_own_services/card",
                    compact("content")
                );
            })
            ->join();

        if (!strlen($userOwnServices)) {
            $userOwnServices = $this->lang->t("no_data");
        }

        $paginationContent = $pagination->createComponent($rowsCount, $request->getPathInfo(), 4);
        $paginationClass = $paginationContent ? "" : "is-hidden";

        return $this->template->render(
            "shop/pages/user_own_services",
            compact("userOwnServices", "paginationClass", "paginationContent")
        );
    }
}
