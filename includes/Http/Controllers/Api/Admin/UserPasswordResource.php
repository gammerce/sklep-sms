<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Models\User;
use App\Http\Responses\ApiResponse;
use App\Translation\TranslationManager;
use App\UserPasswordService;
use Symfony\Component\HttpFoundation\Request;

class UserPasswordResource
{
    public function put(
        $userId,
        Request $request,
        UserPasswordService $userPasswordService,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        $password = $request->request->get("password");
        $user = new User($userId);

        if (!$user->exists()) {
            throw new EntityNotFoundException();
        }

        $userPasswordService->change($userId, $password);

        return new ApiResponse("ok", $lang->translate("change_password_success"), true);
    }
}
