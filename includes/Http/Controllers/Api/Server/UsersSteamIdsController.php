<?php
namespace App\Http\Controllers\Api\Server;

use App\Models\User;
use App\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\Response;

class UsersSteamIdsController
{
    public function get(UserRepository $userRepository)
    {
        $users = $userRepository->allWithSteamId();
        $steamIds = array_map(function (User $user) {
            return $user->getSteamId();
        }, $users);

        return new Response(implode(";", $steamIds) . ";");
    }
}
