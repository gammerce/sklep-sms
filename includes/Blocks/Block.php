<?php
namespace App\Blocks;

use App\Interfaces\IBeLoggedCannot;
use App\Interfaces\IBeLoggedMust;

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
     * @return string|null - zawartość do wyświetlenia
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
     * @return string
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

        return create_dom_element("div", $content, [
            'id' => $this->getContentId(),
            'class' => $content !== null ? $this->getContentClass() : "",
        ]);
    }
}
