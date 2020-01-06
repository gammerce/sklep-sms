<?php
namespace App\Loggers;

use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class DatabaseLogger
{
    /** * @var Database */
    private $db;

    /** @var Translator */
    private $langShop;

    public function __construct(Database $db, TranslationManager $translationManager)
    {
        $this->db = $db;
        $this->langShop = $translationManager->shop();
    }

    public function log($key, ...$args)
    {
        $message = $this->langShop->t($key, ...$args);
        $this->storeLog($message);
    }

    public function logWithActor($key, ...$args)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);

        $message = $this->langShop->t($key, ...$args);
        $this->storeLog($message);

        if ($auth->check()) {
            $user = $auth->user();
            $message .= " | User: {$user->getUsername()}({$user->getUid()})";
        }

        $this->storeLog($message);
    }

    private function storeLog($message)
    {
        $this->db
            ->statement("INSERT INTO `" . TABLE_PREFIX . "logs` SET `text` = ?")
            ->execute([$message]);
    }
}
