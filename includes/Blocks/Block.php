<?php
namespace App\Blocks;

use App\Interfaces\IBeLoggedCannot;
use App\Interfaces\IBeLoggedMust;

abstract class Block
{
    abstract public function get_content_class();

    abstract public function get_content_id();

    /**
     * Zwraca treść danego bloku po przejściu wszystkich filtrów
     *
     * @param array $get - dane get
     * @param array $post - dane post
     *
     * @return string|null - zawartość do wyświetlenia
     */
    public function get_content($get, $post)
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
    public function get_content_enveloped($get, $post)
    {
        $content = $this->get_content($get, $post);

        return create_dom_element("div", $content, [
            'id' => $this->get_content_id(),
            'class' => $content !== null ? $this->get_content_class() : "",
        ]);
    }
}
