<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\System\CronExecutor;

class CronController
{
    public function get(CronExecutor $cronExecutor)
    {
        $cronExecutor->run();
        return new PlainResponse("OK");
    }
}
