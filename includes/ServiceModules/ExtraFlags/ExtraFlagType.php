<?php
namespace App\ServiceModules\ExtraFlags;

use App\Translation\TranslationManager;

class ExtraFlagType
{
    const TYPE_NICK = 1 << 0;
    const TYPE_IP = 1 << 1;
    const TYPE_SID = 1 << 2;

    public static function getTypeName($value)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        if ($value == self::TYPE_NICK) {
            return $lang->t('nickpass');
        }

        if ($value == self::TYPE_IP) {
            return $lang->t('ippass');
        }

        if ($value == self::TYPE_SID) {
            return $lang->t('sid');
        }

        return '';
    }
}
