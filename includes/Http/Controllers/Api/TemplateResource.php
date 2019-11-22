<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\UnauthorizedException;
use App\Http\Responses\PlainResponse;
use App\System\Heart;
use App\System\Template;
use App\Translation\TranslationManager;
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

        $templateName = escape_filename($name);
        $username = htmlspecialchars($request->query->get('username'));
        $email = htmlspecialchars($request->query->get('email'));
        $editedUser = null;

        if ($templateName == "admin_user_wallet") {
            if (!get_privileges("manage_users")) {
                throw new UnauthorizedException();
            }

            $editedUser = $heart->getUser($request->query->get('uid'));
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
