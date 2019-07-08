<?php
namespace App\Services\Other;

use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\Services\Interfaces\IServiceCreate;
use App\Services\Service;

class ServiceOtherSimple extends Service implements
    IServiceCreate,
    IServiceAdminManage,
    IServiceAvailableOnServers
{
    const MODULE_ID = "other";

    public function service_admin_manage_post($data)
    {
        return [];
    }

    public function service_admin_extra_fields_get()
    {
        return '';
    }

    public function service_admin_manage_pre($data)
    {
        return [];
    }
}
