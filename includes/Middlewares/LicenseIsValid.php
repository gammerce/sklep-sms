<?php
namespace App\Middlewares;

use App\Application;
use App\Auth;
use App\License;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseIsValid implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        /** @var License $license */
        $license = $app->make(License::class);

        /** @var Auth $auth */
        $auth = $app->make(Auth::class);
        $user = $auth->user();

        $license->validate();

        if (!$license->isValid()) {
            if (get_privilages("manage_settings", $user)) {
                $user->removePrivilages();
                $user->setPrivilages([
                    "acp"             => true,
                    "manage_settings" => true,
                ]);
            }

            if (SCRIPT_NAME == "index") {
                return new Response($license->getPage());
            }

            if (in_array(SCRIPT_NAME, ["jsonhttp", "servers_stuff", "extra_stuff"])) {
                return new Response();
            }
        }

        return null;
    }
}