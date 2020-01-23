<?php
namespace App\Repositories;

use App\Models\ExtraFlagsUserService;
use App\Models\MybbExtraGroupsUserService;

class UserServiceRepository
{
    public function mapToExtraFlags(array $data)
    {
        return new ExtraFlagsUserService(
            as_int($data['id']),
            $data['service'],
            as_int($data['uid']),
            as_int($data['expire']),
            as_int($data['server']),
            as_int($data['type']),
            $data['auth_data'],
            $data['password']
        );
    }

    public function mapToMybbExtraGroups(array $data)
    {
        return new MybbExtraGroupsUserService(
            as_int($data['id']),
            $data['service'],
            as_int($data['uid']),
            as_int($data['expire']),
            $data['mybb_uid']
        );
    }
}