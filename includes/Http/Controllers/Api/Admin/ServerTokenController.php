<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\ServerRepository;
use App\Translation\TranslationManager;

class ServerTokenController
{
    public function post(
        $serverId,
        ServerRepository $serverRepository,
        DatabaseLogger $databaseLogger,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        $token = $serverRepository->regenerateToken($serverId);
        $databaseLogger->logWithActor("log_server_token_regenerated", $serverId);

        return new SuccessApiResponse($lang->t("server_token_regenerated"), [
            "data" => compact("token"),
        ]);
    }
}
