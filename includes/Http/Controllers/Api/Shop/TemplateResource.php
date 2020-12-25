<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\UnauthorizedException;
use App\Managers\UserManager;
use App\Support\PriceTextService;
use App\Support\Template;
use App\User\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TemplateResource
{
    /** @var Template */
    private $template;

    /** @var UserManager */
    private $userManager;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(
        Template $template,
        UserManager $userManager,
        PriceTextService $priceTextService
    ) {
        $this->template = $template;
        $this->userManager = $userManager;
        $this->priceTextService = $priceTextService;
    }

    public function get($name, Request $request)
    {
        $templateName = escape_filename($name);
        $data = $this->getData($templateName, $request);
        return new JsonResponse($data);
    }

    private function getData($templateName, Request $request)
    {
        $email = $request->query->get("email");

        if ($templateName === "admin_user_wallet") {
            if (cannot(Permission::MANAGE_USERS())) {
                throw new UnauthorizedException();
            }

            $user = $this->userManager->get($request->query->get("user_id"));
            $wallet = $user ? $this->priceTextService->getPriceText($user->getWallet()) : null;

            return [
                "template" => $wallet,
            ];
        }

        return [
            "template" => $this->template->render("jsonhttp/$templateName", compact("email")),
        ];
    }
}
