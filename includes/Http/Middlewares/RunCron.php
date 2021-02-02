<?php
namespace App\Http\Middlewares;

use App\System\CronExecutor;
use App\System\Settings;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class RunCron implements MiddlewareContract
{
    private Settings $settings;
    private CronExecutor $cronExecutor;

    public function __construct(Settings $settings, CronExecutor $cronExecutor)
    {
        $this->settings = $settings;
        $this->cronExecutor = $cronExecutor;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        if ($this->settings["cron_each_visit"]) {
            $this->cronExecutor->run();
        }

        return $next($request);
    }
}
