<?php
namespace App\Http\Middlewares;

use App\Exceptions\LicenseException;
use App\System\Application;
use App\System\Auth;
use App\System\License;
use App\User\Permission;
use Closure;
use Sentry;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateLicense implements MiddlewareContract
{
    private Application $app;
    private Auth $auth;
    private License $license;

    public function __construct(Application $app, Auth $auth, License $license)
    {
        $this->app = $app;
        $this->auth = $auth;
        $this->license = $license;
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        try {
            $this->license->validate();
        } catch (LicenseException $e) {
            $this->limitPrivileges();
            return $next($request);
        }

        // Let's pass some additional info to sentry logger
        // so that it would be easier for us to debug any potential exceptions
        if (class_exists(\Sentry\SentrySdk::class)) {
            Sentry\configureScope(function (Scope $scope) {
                $scope->setTag("license_id", $this->license->getIdentifier());
            });
        } else {
            $this->app->make(\Raven_Client::class)->tags_context([
                "license_id" => $this->license->getIdentifier(),
            ]);
        }

        return $next($request);
    }

    private function limitPrivileges(): void
    {
        $user = $this->auth->user();

        if ($user->can(Permission::MANAGE_SETTINGS())) {
            $user->removePermissions();
            $user->setPermissions([
                "acp" => true,
                "manage_settings" => true,
            ]);
        }
    }
}
