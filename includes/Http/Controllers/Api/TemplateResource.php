<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\UnauthorizedException;
use App\Http\Responses\PlainResponse;
use App\Services\PriceTextService;
use App\System\Heart;
use App\Support\Template;
use Symfony\Component\HttpFoundation\Request;

class TemplateResource
{
    /** @var Template */
    private $template;

    /** @var Heart */
    private $heart;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(
        Template $template,
        Heart $heart,
        PriceTextService $priceTextService
    ) {
        $this->template = $template;
        $this->heart = $heart;
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
        $email = $request->query->get('email');

        if ($templateName === "admin_user_wallet") {
            if (!get_privileges("manage_users")) {
                throw new UnauthorizedException();
            }

            $user = $this->heart->getUser($request->query->get('uid'));
            $wallet = $user ? $this->priceTextService->getPriceText($user->getWallet()) : null;

            return [
                'template' => $wallet,
            ];
        }

        return [
            'template' => $this->template->render("jsonhttp/$templateName", compact('email')),
        ];
    }
}
