<?php
namespace App\Http\Services;

use App\Exceptions\ValidationException;
use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Service;
use App\System\Database;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServiceService
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var Database */
    private $db;

    public function __construct(TranslationManager $translationManager, Heart $heart, Database $db)
    {
        $this->heart = $heart;
        $this->lang = $translationManager->user();
        $this->db = $db;
    }

    public function validateBody(array $body, array $warnings, Service $serviceModule = null)
    {
        $id = array_get($body, 'id');
        $name = array_get($body, 'name');
        $shortDescription = array_get($body, 'short_description');
        $order = array_get($body, 'order');
        $groups = array_get($body, 'groups', []);

        if (!strlen($id)) {
            $warnings['id'][] = $this->lang->translate('no_service_id');
        }

        if (!strlen($name)) {
            $warnings['name'][] = $this->lang->translate('no_service_name');
        }

        if ($warning = check_for_warnings("service_description", $shortDescription)) {
            $warnings['short_description'] = array_merge(
                (array) $warnings['short_description'],
                $warning
            );
        }

        // Kolejność
        if (!my_is_integer($order)) {
            $warnings['order'][] = $this->lang->translate('field_integer');
        }

        // Grupy
        foreach ($groups as $group) {
            if ($this->heart->getGroup($group) === null) {
                $warnings['groups[]'][] = $this->lang->translate('wrong_group');
                break;
            }
        }

        // Przed błędami
        if ($serviceModule instanceof IServiceAdminManage) {
            $additionalWarnings = $serviceModule->serviceAdminManagePre($body);
            $warnings = array_merge((array) $warnings, (array) $additionalWarnings);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }
    }
}
