<?php
namespace App\Http\Controllers\Api\Shop;

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
