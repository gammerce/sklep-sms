<?php
namespace App\Controllers\Api;

use App\Heart;
use App\Responses\ApiResponse;
use App\Responses\PlainResponse;
use App\Template;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class TemplateResource
{
    public function get(
        $name,
        Request $request,
        Template $template,
        TranslationManager $translationManager,
        Heart $heart
    ) {
        $lang = $translationManager->user();

        $templateName = str_replace('/', '_', $name);
        $username = htmlspecialchars($request->request->get('username'));
        $email = htmlspecialchars($request->request->get('email'));
        $editedUser = null;

        if ($template == "admin_user_wallet") {
            if (!get_privileges("manage_users")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $editedUser = $heart->getUser($request->request->get('uid'));
        }

        $data = [
            'template' => $template->render(
                "jsonhttp/" . $templateName,
                compact('username', 'email', 'editedUser')
            ),
        ];

        return new PlainResponse(json_encode($data));
    }
}
