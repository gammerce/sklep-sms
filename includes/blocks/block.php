<?php

use App\Auth;
use App\Settings;
use App\Template;
use App\TranslationManager;

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
        if ((object_implements($this, "I_BeLoggedMust") && !is_logged()) || (object_implements($this,
                    "I_BeLoggedCannot") && is_logged())) {
            return null;
        }

        return $this->content($get, $post);
    }

    /**
     * Zwraca treść danego bloku
     *
     * @param string $get
     * @param string $post
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
            'id'    => $this->get_content_id(),
            'class' => $content !== null ? $this->get_content_class() : "",
        ]);
    }
}

abstract class BlockSimple extends Block
{
    protected $template = null;

    public function __construct()
    {
        if (!isset($this->template)) {
            throw new Exception('Class ' . get_class($this) . ' has to have field $template because it extends class BlockSimple');
        }
    }

    protected function content($get, $post)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();

        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var Settings $settings */
        $settings = app()->make(Settings::class);

        /** @var Template $template */
        $template = app()->make(Template::class);

        return $template->render2($this->template, compact('auth', 'user', 'lang', 'settings'));
    }
}
