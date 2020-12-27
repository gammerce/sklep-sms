<?php
namespace App\View\Html;

use App\Models\Server;
use App\Server\ServerType;
use App\Translation\TranslationManager;

class PlatformCell extends Cell
{
    public function __construct($platform)
    {
        parent::__construct(
            (new Div($this->translatePlatform($platform)))->addClass("one_line"),
            "platform"
        );
    }

    /**
     * @param string $platform
     * @return string
     */
    private function translatePlatform($platform)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        if (in_array($platform, ["engine_amxx", ServerType::AMXMODX])) {
            return $lang->t("amxx_server");
        }

        if (in_array($platform, ["engine_sm", ServerType::SOURCEMOD])) {
            return $lang->t("sm_server");
        }

        return $platform;
    }
}
