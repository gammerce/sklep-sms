<?php
namespace App\View\Blocks;

use App\View\Html\I_ToHtml;
use App\View\Html\RawText;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;

abstract class Block
{
    /**
     * Zwraca treść danego bloku w otoczce
     *
     * @param array $query
     * @param array $body
     * @param array $params
     * @return string|null
     */
    public function getContentEnveloped(array $query, array $body, array $params)
    {
        $content = $this->getContent($query, $body, $params);

        return create_dom_element("div", new RawText($content), [
            'id' => $this->getContentId(),
            'class' => $content !== null ? $this->getContentClass() : "",
        ]);
    }

    /**
     * Zwraca treść danego bloku po przejściu wszystkich filtrów
     *
     * @param array $query
     * @param array $body
     * @param array $params
     *
     * @return I_ToHtml|string|null
     */
    public function getContent(array $query, array $body, array $params)
    {
        if (
            ($this instanceof IBeLoggedMust && !is_logged()) ||
            ($this instanceof IBeLoggedCannot && is_logged())
        ) {
            return null;
        }

        return $this->content($query, $body, $params);
    }

    public function getContentClass()
    {
        return "";
    }

    /**
     * Zwraca treść danego bloku
     *
     * @param array $query
     * @param array $body
     * @param array $params
     *
     * @return I_ToHtml|string
     */
    abstract protected function content(array $query, array $body, array $params);

    abstract public function getContentId();
}
