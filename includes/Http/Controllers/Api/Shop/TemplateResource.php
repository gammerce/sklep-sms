<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\UnauthorizedException;
use App\Http\Responses\PlainResponse;
use App\Managers\UserManager;
use App\Services\PriceTextService;
use App\Support\Template;
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
        return new PlainResponse(json_encode($data));
    }

    private function getData($templateName, Request $request)
    {
        $email = $request->query->get("email");

        if ($templateName === "admin_user_wallet") {
            if (!has_privileges("manage_users")) {
                throw new UnauthorizedException();
            }

            $user = $this->userManager->getUser($request->query->get("user_id"));
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
