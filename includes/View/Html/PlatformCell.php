<?php
namespace App\View\Html;

use App\Server\Platform;

class PlatformCell extends Cell
{
    public function __construct($platform)
    {
        parent::__construct(
            (new Div($this->translatePlatform($platform)))->addClass("one-line"),
            "platform"
        );
    }

    /**
     * @param string $platform
     * @return string
     */
    private function translatePlatform($platform): string
    {
        $translations = [
            Platform::AMXMODX => __("amxx_server"),
            Platform::SOURCEMOD => __("sm_server"),
        ];

        return array_get($translations, $platform, $platform);
    }
}
