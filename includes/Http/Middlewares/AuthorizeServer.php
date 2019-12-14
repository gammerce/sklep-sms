<?php
namespace App\Http\Middlewares;

use App\Repositories\UserRepository;
use App\System\Application;
use App\System\Auth;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeServer implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Settings $settings */
        $settings = $app->make(Settings::class);

        /** @var UserRepository $userRepository */
        $userRepository = $app->make(UserRepository::class);

        /** @var Auth $auth */
        $auth = $app->make(Auth::class);

        $key = $request->query->get("key");

        if ($key !== md5($settings['random_key'])) {
            return new Response("Invalid key", 400);
        }

        $steamId = $request->headers->get("Authorization");
        $user = $userRepository->findBySteamId($steamId);

        if ($user) {
            $auth->setUser($user);
        }

        return null;
    }
}
