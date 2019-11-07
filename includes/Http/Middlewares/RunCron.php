<?php
namespace App\Http\Middlewares;

use App\System\Application;
use App\System\CronExecutor;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class RunCron implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        $settings = $app->make(Settings::class);

        if ($settings['cron_each_visit']) {
            /** @var CronExecutor $cronExecutor */
            $cronExecutor = $app->make(CronExecutor::class);
            $cronExecutor->run();
        }

        return null;
    }
}
