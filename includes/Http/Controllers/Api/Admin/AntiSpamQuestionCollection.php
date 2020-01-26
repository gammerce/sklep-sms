<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Repositories\AntiSpamQuestionRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class AntiSpamQuestionCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        AntiSpamQuestionRepository $repository
    ) {
        $lang = $translationManager->user();

        $validator = new Validator($request->request->all(), [
            "question" => [new RequiredRule()],
            "answers" => [new RequiredRule()],
        ]);

        $validated = $validator->validateOrFail();

        $repository->create($validated['question'], $validated['answers']);

        return new SuccessApiResponse($lang->t('antispam_add'));
    }
}
