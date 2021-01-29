<?php
namespace App\View\Renders;

use App\Support\Template;
use Symfony\Component\HttpFoundation\Request;

class ErrorRenderer
{
    private ShopRenderer $shopRenderer;
    private Template $template;

    public function __construct(ShopRenderer $shopRenderer, Template $template)
    {
        $this->shopRenderer = $shopRenderer;
        $this->template = $template;
    }

    public function render($errorId, Request $request)
    {
        $content = $this->template->render("errors/$errorId");
        return $this->shopRenderer->render($content, "error", "$errorId", $request);
    }
}
