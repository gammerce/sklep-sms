<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\AntiSpamQuestionRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class AntispamQuestionResource
{
    public function delete(
        $antispamQuestionId,
        AntiSpamQuestionRepository $repository,
        TranslationManager $translationManager,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();

        $deleted = $repository->delete($antispamQuestionId);

        if ($deleted) {
            $databaseLogger->logWithActor('log_question_deleted', $antispamQuestionId);
            return new SuccessApiResponse($lang->t('delete_antispamq'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_antispamq'), 0);
    }

    public function put(
        $antispamQuestionId,
        Request $request,
        AntiSpamQuestionRepository $repository,
        DatabaseLogger $databaseLogger,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        $validator = new Validator($request->request->all(), [
            "question" => [new RequiredRule()],
            "answers" => [new RequiredRule()],
        ]);

        $validated = $validator->validateOrFail();

        $updated = $repository->update(
            $antispamQuestionId,
            $validated['question'],
            $validated['answers']
        );

        if ($updated) {
            $databaseLogger->logWithActor('log_question_edited', $antispamQuestionId);
            return new SuccessApiResponse($lang->t('antispam_edit'));
        }

        return new ApiResponse("not_edited", $lang->t('antispam_no_edit'), 0);
    }
}
