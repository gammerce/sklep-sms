<?php
namespace App\Http\Middlewares;

use App\Repositories\ServerRepository;
use App\Repositories\UserRepository;
use App\System\Application;
use App\System\Auth;
use App\System\ServerAuth;
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

        /** @var ServerRepository $serverRepository */
        $serverRepository = $app->make(ServerRepository::class);

        /** @var Auth $auth */
        $auth = $app->make(Auth::class);

        /** @var ServerAuth $serverAuth */
        $serverAuth = $app->make(ServerAuth::class);

        // TODO Remove authorization by key
        $key = $request->query->get("key");
        $token = $request->query->get("token");
        $steamId = $request->headers->get("Authorization");

        $server = $serverRepository->findByToken($token);

        if (!$server && $key !== md5($settings->getSecret())) {
            return new Response("Server unauthorized", 400);
        }

        $user = $userRepository->findBySteamId($steamId);

        if ($server) {
            $serverAuth->setServer($server);
        }

        if ($user) {
            $auth->setUser($user);
        }

        return null;
    }
}
