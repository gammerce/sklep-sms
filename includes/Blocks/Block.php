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
     * @param array $get - dane get
     * @param array $post - dane post
     *
     * @return string|null - zawartość do wyświetlenia
     */
    public function getContent($get, $post)
    {
        if (
            ($this instanceof IBeLoggedMust && !is_logged()) ||
            ($this instanceof IBeLoggedCannot && is_logged())
        ) {
            return null;
        }

        return $this->content($get, $post);
    }

    /**
     * Zwraca treść danego bloku
     *
     * @param array $get
     * @param array $post
     *
     * @return string
     */
    abstract protected function content($get, $post);

    /**
     * Zwraca treść danego bloku w otoczce
     *
     * @param array $get
     * @param array $post
     *
     * @return string|null
     */
    public function getContentEnveloped($get, $post)
    {
        $content = $this->getContent($get, $post);

        return create_dom_element("div", $content, [
            'id' => $this->getContentId(),
            'class' => $content !== null ? $this->getContentClass() : "",
        ]);
    }
}
