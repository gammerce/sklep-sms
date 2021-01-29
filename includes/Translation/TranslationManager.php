<?php
namespace App\Translation;

class TranslationManager
{
    private ?Translator $user = null;
    private ?Translator $shop = null;

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
