<?php
namespace App\License\ServiceModules\ShopSmsLicense;

use App\Server\Platform;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PlatformService
{
    private Translator $lang;

    public function __construct(TranslationManager $translationManager)
    {
        $this->lang = $translationManager->user();
    }

    public function formatPlatforms(array $platforms): string
    {
        if (empty($platforms)) {
            return $this->lang->t("none");
        }

        $platformsNames = [
            Platform::AMXMODX => "AMX Mod X",
            Platform::SOURCEMOD => "SOURCEMOD",
        ];

        return collect($platforms)
            ->map(function ($platform) {
                if ($platform instanceof Platform) {
                    return $platform->getValue();
                }

                return $platform;
            })
            ->map(fn($platform) => $platformsNames[$platform])
            ->join(", ");
    }
}
