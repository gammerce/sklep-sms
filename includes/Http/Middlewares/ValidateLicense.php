<?php
namespace App\Http\Middlewares;

use App\Application;
use App\Auth;
use App\Exceptions\LicenseException;
use App\License;
use Raven_Client;
use Symfony\Component\HttpFoundation\Request;

class ValidateLicense implements MiddlewareContract
{
    /** @var Auth */
    private $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var License $license */
        $license = $app->make(License::class);

        try {
            $license->validate();
        } catch (LicenseException $e) {
            $this->limitPrivileges();
            return null;
        }

        // Let's pass some additional info to sentry logger
        // so that it would be easier for us to debug any potential exceptions
        if ($app->bound(Raven_Client::class)) {
            $app->make(Raven_Client::class)->tags_context([
                'license_id' => $license->getExternalId(),
            ]);
        }

        return null;
    }

    private function limitPrivileges()
    {
        $user = $this->auth->user();

        if (get_privileges("manage_settings", $user)) {
            $user->removePrivileges();
            $user->setPrivileges([
                "acp" => true,
                "manage_settings" => true,
            ]);
        }
    }
}
