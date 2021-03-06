<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\Repositories\UserRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserPasswordResource
{
    public function put(
        $userId,
        Request $request,
        UserRepository $userRepository,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        $password = $request->request->get("password");
        $user = $userRepository->get($userId);

        if (!$user) {
            throw new EntityNotFoundException();
        }

        $userRepository->updatePassword($userId, $password);

        return new ApiResponse("ok", $lang->t("change_password_success"), true);
    }
}
