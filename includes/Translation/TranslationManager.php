<?php
namespace App\Translation;

class TranslationManager
{
    /** @var Translator */
    protected $user;

    /** @var Translator */
    protected $shop;

    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        return $this->user = new Translator();
    }

    public function shop()
    {
        if ($this->shop !== null) {
            return $this->shop;
        }

        return $this->shop = new Translator();
    }
}
