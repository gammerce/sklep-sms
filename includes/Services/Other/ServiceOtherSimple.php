<?php
namespace App\Services\Other;

use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\Services\Interfaces\IServiceCreate;
use App\Services\Service;

abstract class ServiceOtherSimple extends Service implements
    IServiceCreate,
    IServiceAdminManage,
    IServiceAvailableOnServers
{
    const MODULE_ID = "other";

    public function serviceAdminManagePost($data)
    {
        return [];
    }

    public function serviceAdminExtraFieldsGet()
    {
        return '';
    }

    public function serviceAdminManagePre($data)
    {
        return [];
    }
}
