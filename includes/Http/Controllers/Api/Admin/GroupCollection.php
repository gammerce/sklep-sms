<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\GroupRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class GroupCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        GroupRepository $groupRepository,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();
        $name = $request->request->get("name");
        $permissions = $request->request->get("permissions");

        $group = $groupRepository->create($name, as_permission_list($permissions));
        $databaseLogger->logWithActor("log_group_added", $group->getId());

        return new SuccessApiResponse($lang->t("group_add"), [
            "data" => [
                "id" => $group->getId(),
            ],
        ]);
    }
}
