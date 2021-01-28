<?php
namespace App\Providers;

use App\Cache\FileCache;
use App\Loggers\DatabaseLogger;
use App\Loggers\FileLogger;
use App\Managers\BlockManager;
use App\Managers\GroupManager;
use App\Managers\PageManager;
use App\Managers\PaymentModuleManager;
use App\Managers\ServerManager;
use App\Managers\ServerServiceManager;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Managers\UserManager;
use App\Managers\WebsiteHeader;
use App\Support\Database;
use App\Support\FileSystem;
use App\Support\FileSystemContract;
use App\Support\Mailer;
use App\Support\Path;
use App\Support\Template;
use App\System\Application;
use App\System\Auth;
use App\System\ExternalConfigProvider;
use App\System\License;
use App\System\ServerAuth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\View\Pages\Shop\PageRegister;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class AppServiceProvider
{
    public function register(Application $app)
    {
        $app->bind(FileSystemContract::class, FileSystem::class);

        $this->registerDatabase($app);
        $this->registerCache($app);
        $this->registerMailer($app);
        $this->registerPageRegister($app);

        $app->singleton(Auth::class);
        $app->singleton(BlockManager::class);
        $app->singleton(ExternalConfigProvider::class);
        $app->singleton(GroupManager::class);
        $app->singleton(License::class);
        $app->singleton(PageManager::class);
        $app->singleton(PaymentModuleManager::class);
        $app->singleton(ServerAuth::class);
        $app->singleton(ServerManager::class);
        $app->singleton(ServiceManager::class);
        $app->singleton(ServiceModuleManager::class);
        $app->singleton(ServerServiceManager::class);
        $app->singleton(Session::class);
        $app->singleton(Settings::class);
        $app->singleton(TranslationManager::class);
        $app->singleton(UserManager::class);
        $app->singleton(WebsiteHeader::class);
    }

    private function registerDatabase(Application $app)
    {
        $app->singleton(
            Database::class,
            fn() => new Database(
                getenv("DB_HOST"),
                getenv("DB_PORT") ?: 3306,
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD"),
                getenv("DB_DATABASE")
            )
        );
    }

    private function registerCache(Application $app)
    {
        $app->bind(FileCache::class, function (Application $app) {
            /** @var Path $path */
            $path = $app->make(Path::class);
            return new FileCache($app->make(FileSystemContract::class), $path->to("data/cache"));
        });
        $app->bind(CacheInterface::class, FileCache::class);
    }

    private function registerMailer(Application $app)
    {
        $app->bind(Mailer::class, function (Application $app) {
            $config = [
                "host" => getenv("MAIL_HOST"),
                "password" => getenv("MAIL_PASSWORD"),
                "port" => getenv("MAIL_PORT") ?: 587,
                "secure" => getenv("MAIL_SECURE") ?: PHPMailer::ENCRYPTION_STARTTLS,
                "username" => getenv("MAIL_USERNAME"),
                "disable_cert_validation" => boolval(getenv("MAIL_DISABLE_CERT_VALIDATION")),
            ];
            return new Mailer(
                $app->make(Settings::class),
                $app->make(DatabaseLogger::class),
                $app->make(FileLogger::class),
                $config
            );
        });
    }

    private function registerPageRegister(Application $app)
    {
        $app->bind(PageRegister::class, function (Application $app) {
            /** @var ExternalConfigProvider $configProvider */
            $configProvider = $app->make(ExternalConfigProvider::class);
            return new PageRegister(
                $app->make(Template::class),
                $app->make(TranslationManager::class),
                $configProvider->captchaSiteKey()
            );
        });
    }
}
