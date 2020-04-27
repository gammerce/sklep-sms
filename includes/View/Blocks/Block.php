<?php
namespace App\View\Blocks;

use App\View\Html\I_ToHtml;
use App\View\Html\RawText;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

abstract class Block
{
    /**
     * @param Request $request
     * @param array $params
     * @return string|null
     */
    public function getContentEnveloped(Request $request, array $params)
    {
        $content = $this->getContent($request, $params);

        return create_dom_element("div", new RawText($content), [
            'id' => $this->getContentId(),
            'class' => $content !== null ? $this->getContentClass() : "",
        ]);
    }

    /**
     * @param Request $request
     * @param array $params
     *
     * @return I_ToHtml|string|null
     */
    public function getContent(Request $request, array $params)
    {
        if (
            ($this instanceof IBeLoggedMust && !is_logged()) ||
            ($this instanceof IBeLoggedCannot && is_logged())
        ) {
            return null;
        }

        return $this->content($request, $params);
    }

    /**
     * @param Request $request
     * @param array $params
     *
     * @return I_ToHtml|string
     */
    abstract protected function content(Request $request, array $params);

    abstract public function getContentId();

    abstract public function getContentClass();
}
