<?php
namespace App\View\Renders;

use App\System\Heart;
use App\System\License;
use App\System\Template;
use App\View\CurrentPage;
use Symfony\Component\HttpFoundation\Request;

class ShopRenderer
{
    /** @var Template */
    private $template;

    /** @var Heart */
    private $heart;

    /** @var License */
    private $license;

    /** @var CurrentPage */
    private $currentPage;

    /** @var BlockRenderer */
    private $blockRenderer;

    public function __construct(
        Template $template,
        Heart $heart,
        License $license,
        CurrentPage $currentPage,
        BlockRenderer $blockRenderer
    ) {
        $this->template = $template;
        $this->heart = $heart;
        $this->license = $license;
        $this->currentPage = $currentPage;
        $this->blockRenderer = $blockRenderer;
    }

    public function render($content, $pageTitle, Request $request)
    {
        $header = $this->template->render("header", [
            'currentPage' => $this->currentPage,
            'heart' => $this->heart,
            'license' => $this->license,
            'pageTitle' => $pageTitle,
        ]);
        $loggedInfo = $this->blockRenderer->render("logged_info", $request);
        $wallet = $this->blockRenderer->render("wallet", $request);
        $servicesButtons = $this->blockRenderer->render("services_buttons", $request);
        $userButtons = $this->blockRenderer->render("user_buttons", $request);
        $googleAnalytics = $this->heart->getGoogleAnalytics();

        return $this->template->render(
            "index",
            compact(
                "content",
                "googleAnalytics",
                "header",
                "heart",
                "loggedInfo",
                "pageTitle",
                "servicesButtons",
                "userButtons",
                "wallet"
            )
        );
    }
}
