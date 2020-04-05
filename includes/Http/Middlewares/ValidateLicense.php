<?php
namespace App\Http\Middlewares;

use App\Exceptions\LicenseException;
use App\System\Application;
use App\System\Auth;
use App\System\License;
use Closure;
use Raven_Client;
use Symfony\Component\HttpFoundation\Request;

class ValidateLicense implements MiddlewareContract
{
    /** @var Application */
    private $app;

    /** @var Auth */
    private $auth;

    /** @var License */
    private $license;

    public function __construct(Application $app, Auth $auth, License $license)
    {
        $this->app = $app;
        $this->auth = $auth;
        $this->license = $license;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        try {
            $this->license->validate();
        } catch (LicenseException $e) {
            $this->limitPrivileges();
            return $next($request);
        }

        // Let's pass some additional info to sentry logger
        // so that it would be easier for us to debug any potential exceptions
        if ($this->app->bound(Raven_Client::class)) {
            $this->app->make(Raven_Client::class)->tags_context([
                'license_id' => $this->license->getExternalId(),
            ]);
        }

        return $next($request);
    }

    private function limitPrivileges()
    {
        $user = $this->auth->user();

        if (has_privileges("manage_settings", $user)) {
            $user->removePrivileges();
            $user->setPrivileges([
                "acp" => true,
                "manage_settings" => true,
            ]);
        }
    }
}
