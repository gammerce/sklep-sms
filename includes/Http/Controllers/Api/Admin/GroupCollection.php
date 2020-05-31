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

        $set = collect($groupRepository->getFields())
            ->filter(function ($fieldName) {
                return !in_array($fieldName, ["id", "name"], true);
            })
            ->flatMap(function ($fieldName) use ($request) {
                return ["`$fieldName`" => $request->request->get($fieldName) ?: 0];
            })
            ->all();

        $group = $groupRepository->create($name, $set);

        $databaseLogger->logWithActor("log_group_added", $group->getId());

        return new SuccessApiResponse($lang->t("group_add"), [
            "data" => [
                "id" => $group->getId(),
            ],
        ]);
    }
}
