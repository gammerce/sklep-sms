<?php
namespace App\ServiceModules\MybbExtraGroups;

class MybbRepositoryFactory
{
    public function create($host, $port, $username, $password, $database)
    {
        return new MybbRepository($host, $port, $username, $password, $database);
    }
}
