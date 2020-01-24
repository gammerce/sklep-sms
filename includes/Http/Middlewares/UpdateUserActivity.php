<?php
namespace App\Http\Middlewares;

use App\System\Application;
use App\System\Auth;
use App\User\UserActivityService;
use Symfony\Component\HttpFoundation\Request;

class UpdateUserActivity implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Auth $auth */
        $auth = $app->make(Auth::class);

        /** @var UserActivityService $activityService */
        $activityService = $app->make(UserActivityService::class);

        $user = $auth->user();
        $user->setLastIp(get_ip($request));
        $activityService->update($user);

        return null;
    }
}
