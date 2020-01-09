<?php
namespace App\View\Pages;

use App\Services\UserServiceService;
use App\View\Interfaces\IBeLoggedMust;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\System\Auth;
use App\System\Database;
use App\System\Settings;
use App\System\Template;
use App\View\Pagination;
use Symfony\Component\HttpFoundation\Request;

class PageUserOwnServices extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'user_own_services';

    /** @var UserServiceService */
    private $userServiceService;

    public function __construct(UserServiceService $userServiceService)
    {
        parent::__construct();

        $this->userServiceService = $userServiceService;
        $this->heart->pageTitle = $this->title = $this->lang->t('user_own_services');
    }

    protected function content(array $query, array $body)
    {
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        /** @var Request $request */
        $request = $this->app->make(Request::class);

        /** @var Pagination $pagination */
        $pagination = $this->app->make(Pagination::class);

        // Ktore moduly wspieraja usługi użytkowników
        $modules = array_filter($this->heart->getServicesModules(), function ($module) {
            return in_array(IServiceUserOwnServices::class, class_implements($module["class"]));
        });

        $moduleIds = [];
        foreach ($modules as $module) {
            $moduleIds[] = $module["id"];
        }

        $usersServices = [];
        $rowsCount = 0;
        if (!empty($moduleIds)) {
            $moduleIds = implode_esc(', ', $moduleIds);

            $rowsCount = $db
                ->query(
                    $db->prepare(
                        "SELECT COUNT(*) as `amount` FROM `" .
                            TABLE_PREFIX .
                            "user_service` AS us " .
                            "INNER JOIN `" .
                            TABLE_PREFIX .
                            "services` AS s ON us.service = s.id " .
                            "WHERE us.uid = '%d' AND s.module IN ({$moduleIds}) ",
                        [$user->getUid()]
                    )
                )
                ->fetchColumn();

            $result = $db->query(
                $db->prepare(
                    "SELECT us.id FROM `" .
                        TABLE_PREFIX .
                        "user_service` AS us " .
                        "INNER JOIN `" .
                        TABLE_PREFIX .
                        "services` AS s ON us.service = s.id " .
                        "WHERE us.uid = '%d' AND s.module IN ({$moduleIds}) " .
                        "ORDER BY us.id DESC " .
                        "LIMIT " .
                        get_row_limit($this->currentPage->getPageNumber(), 4),
                    [$user->getUid()]
                )
            );

            $userServiceIds = [];
            foreach ($result as $row) {
                $userServiceIds[] = $row['id'];
            }

            if (!empty($userServiceIds)) {
                $usersServices = $this->userServiceService->find(
                    "WHERE us.id IN (" . implode(', ', $userServiceIds) . ")",
                    false
                );
            }
        }

        $userOwnServices = '';
        foreach ($usersServices as $userService) {
            if (
                ($serviceModule = $this->heart->getServiceModule($userService['service'])) === null
            ) {
                continue;
            }

            if (!($serviceModule instanceof IServiceUserOwnServices)) {
                continue;
            }

            if (
                $settings['user_edit_service'] &&
                $serviceModule instanceof IServiceUserOwnServicesEdit
            ) {
                $buttonEdit = create_dom_element("button", $this->lang->t('edit'), [
                    'class' => "button is-small edit_row",
                    'type' => 'button',
                ]);
            }

            $userOwnServices .= create_brick(
                $serviceModule->userOwnServiceInfoGet(
                    $userService,
                    isset($buttonEdit) ? $buttonEdit : ''
                )
            );
        }

        // Nie znalazło żadnych usług danego użytkownika
        if (!strlen($userOwnServices)) {
            $userOwnServices = $this->lang->t('no_data');
        }

        $paginationContent = $pagination->getPagination(
            $rowsCount,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $query,
            4
        );
        $paginationClass = $paginationContent ? "" : "display_none";

        return $template->render(
            "user_own_services",
            compact('userOwnServices', 'paginationClass', 'paginationContent')
        );
    }
}
