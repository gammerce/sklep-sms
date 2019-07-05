<?php

use App\Auth;
use App\Database;
use App\Interfaces\IBeLoggedMust;
use App\Settings;
use App\Template;
use Symfony\Component\HttpFoundation\Request;

class Page_UserOIwnServices extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'user_own_services';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('user_own_services');
    }

    protected function content($get, $post)
    {
        $heart = $this->heart;
        $lang = $this->lang;

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

        // Ktore moduly wspieraja usługi użytkowników
        $classes = array_filter(get_declared_classes(), function ($className) {
            return in_array('IService_UserOwnServices', class_implements($className));
        });

        $modules = [];
        foreach ($classes as $class) {
            $modules[] = $class::MODULE_ID;
        }

        $users_services = [];
        $rows_count = 0;
        if (!empty($modules)) {
            $modules = implode_esc(', ', $modules);

            $rows_count = $db->get_column(
                $db->prepare(
                    "SELECT COUNT(*) as `amount` FROM `" .
                        TABLE_PREFIX .
                        "user_service` AS us " .
                        "INNER JOIN `" .
                        TABLE_PREFIX .
                        "services` AS s ON us.service = s.id " .
                        "WHERE us.uid = '%d' AND s.module IN ({$modules}) ",
                    [$user->getUid()]
                ),
                'amount'
            );

            $result = $db->query(
                $db->prepare(
                    "SELECT us.id FROM `" .
                        TABLE_PREFIX .
                        "user_service` AS us " .
                        "INNER JOIN `" .
                        TABLE_PREFIX .
                        "services` AS s ON us.service = s.id " .
                        "WHERE us.uid = '%d' AND s.module IN ({$modules}) " .
                        "ORDER BY us.id DESC " .
                        "LIMIT " .
                        get_row_limit($this->currentPage->getPageNumber(), 4),
                    [$user->getUid()]
                )
            );

            $user_service_ids = [];
            while ($row = $db->fetch_array_assoc($result)) {
                $user_service_ids[] = $row['id'];
            }

            if (!empty($user_service_ids)) {
                $users_services = get_users_services(
                    "WHERE us.id IN (" . implode(', ', $user_service_ids) . ")",
                    false
                );
            }
        }

        $user_own_services = '';
        foreach ($users_services as $user_service) {
            if (($service_module = $heart->get_service_module($user_service['service'])) === null) {
                continue;
            }

            if (!($service_module instanceof IService_UserOwnServices)) {
                continue;
            }

            if (
                $settings['user_edit_service'] &&
                $service_module instanceof IService_UserOwnServicesEdit
            ) {
                $button_edit = create_dom_element("button", $lang->translate('edit'), [
                    'class' => "button edit_row",
                    'type' => 'button',
                ]);
            }

            $user_own_services .= create_brick(
                $service_module->user_own_service_info_get(
                    $user_service,
                    if_isset($button_edit, '')
                )
            );
        }

        // Nie znalazło żadnych usług danego użytkownika
        if (!strlen($user_own_services)) {
            $user_own_services = $lang->translate('no_data');
        }

        $pagination = get_pagination(
            $rows_count,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $get,
            4
        );
        $pagination_class = strlen($pagination) ? "" : "display_none";

        return $template->render(
            "user_own_services",
            compact('user_own_services', 'pagination_class', 'pagination')
        );
    }
}
