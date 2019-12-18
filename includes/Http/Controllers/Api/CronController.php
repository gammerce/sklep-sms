<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\System\CronExecutor;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class CronController
{
    public function get(Request $request, Settings $settings, CronExecutor $cronExecutor)
    {
        if ($request->query->get('key') != $settings['random_key']) {
            return new PlainResponse("Invalid key.");
        }

        $cronExecutor->run();

        return new PlainResponse("OK");
    }
}
