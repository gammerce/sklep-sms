<?php
namespace App\View\Renders;

use App\System\Template;
use Symfony\Component\HttpFoundation\Request;

class ErrorRenderer
{
    /** @var ShopRenderer */
    private $shopRenderer;

    /** @var Template */
    private $template;

    public function __construct(ShopRenderer $shopRenderer, Template $template)
    {
        $this->shopRenderer = $shopRenderer;
        $this->template = $template;
    }

    public function render($errorId, Request $request)
    {
        $content = $this->template->render("errors/$errorId");
        return $this->shopRenderer->render($content, "$errorId", $request);
    }
}
