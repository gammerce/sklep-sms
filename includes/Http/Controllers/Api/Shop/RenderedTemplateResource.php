<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\UnauthorizedException;
use App\Managers\UserManager;
use App\Support\PriceTextService;
use App\Theme\Template;
use App\User\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RenderedTemplateResource
{
    public function get(
        $name,
        Request $request,
        Template $template,
        UserManager $userManager,
        PriceTextService $priceTextService
    ) {
        $templateName = escape_filename($name);
        $data = $this->getData($templateName, $request, $template, $userManager, $priceTextService);
        return new JsonResponse($data);
    }

    private function getData(
        $templateName,
        Request $request,
        Template $template,
        UserManager $userManager,
        PriceTextService $priceTextService
    ) {
        $email = $request->query->get("email");

        if ($templateName === "admin_user_wallet") {
            if (cannot(Permission::USERS_MANAGEMENT())) {
                throw new UnauthorizedException();
            }

            $user = $userManager->get($request->query->get("user_id"));
            $wallet = $user ? $priceTextService->getPriceText($user->getWallet()) : null;

            return [
                "template" => $wallet,
            ];
        }

        return [
            "template" => $template->render("jsonhttp/$templateName", compact("email")),
        ];
    }
}
