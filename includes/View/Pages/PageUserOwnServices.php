<?php
namespace App\View\Pages;

use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\ServiceModules\ServiceModule;
use App\Services\UserServiceService;
use App\System\Auth;
use App\System\Settings;
use App\View\Interfaces\IBeLoggedMust;
use App\View\PaginationService;
use Symfony\Component\HttpFoundation\Request;

class PageUserOwnServices extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'user_own_services';

    /** @var UserServiceService */
    private $userServiceService;

    /** @var Settings */
    private $settings;

    public function __construct(UserServiceService $userServiceService, Settings $settings)
    {
        parent::__construct();

        $this->userServiceService = $userServiceService;
        $this->heart->pageTitle = $this->title = $this->lang->t('user_own_services');
        $this->settings = $settings;
    }

    protected function content(array $query, array $body)
    {
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var Request $request */
        $request = $this->app->make(Request::class);

        /** @var PaginationService $pagination */
        $pagination = $this->app->make(PaginationService::class);

        $moduleIds = collect($this->heart->getEmptyServiceModules())
            ->filter(function (ServiceModule $serviceModule) {
                return $serviceModule instanceof IServiceUserOwnServices;
            })
            ->map(function (ServiceModule $serviceModule) {
                return $serviceModule->getModuleId();
            });

        $usersServices = [];
        $rowsCount = 0;
        if ($moduleIds->isPopulated()) {
            $keys = $moduleIds
                ->map(function () {
                    return "?";
                })
                ->join(", ");

            $statement = $this->db->statement(
                "SELECT COUNT(*) FROM `ss_user_service` AS us " .
                    "INNER JOIN `ss_services` AS s ON us.service = s.id " .
                    "WHERE us.uid = ? AND s.module IN ({$keys}) "
            );
            $statement->execute(array_merge([$user->getUid()], $moduleIds->all()));
            $rowsCount = $statement->fetchColumn();

            $statement = $this->db->statement(
                "SELECT us.id FROM `ss_user_service` AS us " .
                    "INNER JOIN `ss_services` AS s ON us.service = s.id " .
                    "WHERE us.uid = ? AND s.module IN ({$keys}) " .
                    "ORDER BY us.id DESC " .
                    "LIMIT ?, ?"
            );
            $statement->execute(
                array_merge(
                    [$user->getUid()],
                    $moduleIds->all(),
                    get_row_limit($this->currentPage->getPageNumber(), 4)
                )
            );

            $userServiceIds = collect($statement)
                ->map(function (array $row) {
                    return $row['id'];
                })
                ->join(", ");

            if ($userServiceIds) {
                $usersServices = $this->userServiceService->find(
                    "WHERE us.id IN ({$userServiceIds})"
                );
            }
        }

        $userOwnServices = '';
        foreach ($usersServices as $userService) {
            $serviceModule = $this->heart->getServiceModule($userService->getServiceId());

            if (!($serviceModule instanceof IServiceUserOwnServices)) {
                continue;
            }

            if (
                $this->settings['user_edit_service'] &&
                $serviceModule instanceof IServiceUserOwnServicesEdit
            ) {
                $buttonEdit = create_dom_element("button", $this->lang->t('edit'), [
                    'class' => "button is-small edit_row",
                    'type' => 'button',
                ]);
            }

            $userOwnServices .= $this->template->render("admin/brick_card", [
                'content' => $serviceModule->userOwnServiceInfoGet(
                    $userService,
                    isset($buttonEdit) ? $buttonEdit : ''
                ),
            ]);
        }

        if (!strlen($userOwnServices)) {
            $userOwnServices = $this->lang->t('no_data');
        }

        $paginationContent = $pagination->createPagination(
            $rowsCount,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $query,
            4
        );
        $paginationClass = $paginationContent ? "" : "display_none";

        return $this->template->render(
            "user_own_services",
            compact('userOwnServices', 'paginationClass', 'paginationContent')
        );
    }
}
