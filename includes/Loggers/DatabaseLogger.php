<?php
namespace App\Loggers;

use App\Models\User;
use App\Repositories\LogRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class DatabaseLogger
{
    private Translator $langShop;
    private Auth $auth;
    private LogRepository $logRepository;

    public function __construct(
        LogRepository $logRepository,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $this->langShop = $translationManager->shop();
        $this->auth = $auth;
        $this->logRepository = $logRepository;
    }

    public function log($key, ...$args): void
    {
        $message = $this->langShop->t($key, ...$args);
        $this->logRepository->create($message);
    }

    public function logWithActor($key, ...$args): void
    {
        $message = $this->langShop->t($key, ...$args);

        if ($this->auth->check()) {
            $user = $this->auth->user();
            $message .= " | User: {$user->getUsername()}({$user->getId()})({$user->getLastIp()})";
        }

        $this->logRepository->create($message);
    }

    public function logWithUser(User $user, $key, ...$args): void
    {
        $message = $this->langShop->t($key, ...$args);
        $message .= " | User: {$user->getUsername()}({$user->getId()})({$user->getLastIp()})";
        $this->logRepository->create($message);
    }
}
