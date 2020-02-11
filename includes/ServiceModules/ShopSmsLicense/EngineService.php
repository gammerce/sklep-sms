<?php
namespace App\ServiceModules\ShopSmsLicense;

use App\Translation\TranslationManager;
use App\Translation\Translator;

class EngineService
{
    /** @var Translator */
    private $lang;

    public function __construct(TranslationManager $translationManager)
    {
        $this->lang = $translationManager->user();
    }

    public function formatOrderEngines(array $engines)
    {
        $output = [];

        if (array_get($engines, 'amxx')) {
            $output[] = "AMX Mod X";
        }

        if (array_get($engines, 'sm')) {
            $output[] = "SOURCEMOD";
        }

        return $this->formatEngines($output);
    }

    public function formatEngines(array $engines)
    {
        if ($engines) {
            return implode(", ", $engines);
        }

        return $this->lang->t('none');
    }
}
