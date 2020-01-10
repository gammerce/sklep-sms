<?php
namespace App\View\Blocks;

use App\View\Html\I_ToHtml;
use App\View\Html\UnescapedSimpleText;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;

abstract class Block
{
    abstract public function getContentClass();

    abstract public function getContentId();

    /**
     * Zwraca treść danego bloku po przejściu wszystkich filtrów
     *
     * @param array $query
     * @param array $body
     *
     * @return I_ToHtml|string|null - zawartość do wyświetlenia
     */
    public function getContent(array $query, array $body)
    {
        if (
            ($this instanceof IBeLoggedMust && !is_logged()) ||
            ($this instanceof IBeLoggedCannot && is_logged())
        ) {
            return null;
        }

        return $this->content($query, $body);
    }

    /**
     * Zwraca treść danego bloku
     *
     * @param array $query
     * @param array $body
     *
     * @return I_ToHtml|string
     */
    abstract protected function content(array $query, array $body);

    /**
     * Zwraca treść danego bloku w otoczce
     *
     * @param array $query
     * @param array $body
     *
     * @return string|null
     */
    public function getContentEnveloped(array $query, array $body)
    {
        $content = $this->getContent($query, $body);

        return create_dom_element("div", new UnescapedSimpleText($content), [
            'id' => $this->getContentId(),
            'class' => $content !== null ? $this->getContentClass() : "",
        ]);
    }
}
