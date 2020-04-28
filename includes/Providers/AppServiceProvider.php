<?php
namespace App\Providers;

use App\Cache\FileCache;
use App\Support\Database;
use App\Support\FileSystem;
use App\Support\FileSystemContract;
use App\Support\Path;
use App\System\Application;
use App\System\Auth;
use App\System\ExternalConfigProvider;
use App\System\Heart;
use App\System\License;
use App\System\ServerAuth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\View\BlockManager;
use App\View\CurrentPage;
use App\View\PageManager;
use App\View\ServiceModuleManager;
use App\View\WebsiteHeader;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class AppServiceProvider
{
    public function register(Application $app)
    {
        $app->bind(FileSystemContract::class, FileSystem::class);

        $this->registerDatabase($app);
        $this->registerCache($app);

        $app->singleton(Session::class);
        $app->singleton(Heart::class);
        $app->singleton(Auth::class);
        $app->singleton(ServerAuth::class);
        $app->singleton(Settings::class);
        $app->singleton(CurrentPage::class);
        $app->singleton(License::class);
        $app->singleton(TranslationManager::class);
        $app->singleton(ExternalConfigProvider::class);
        $app->singleton(WebsiteHeader::class);
        $app->singleton(PageManager::class);
        $app->singleton(BlockManager::class);
        $app->singleton(ServiceModuleManager::class);
    }

    private function registerDatabase(Application $app)
    {
        $app->singleton(Database::class, function () {
            return new Database(
                getenv('DB_HOST'),
                getenv('DB_PORT') ?: 3306,
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD'),
                getenv('DB_DATABASE')
            );
        });
    }

    private function registerCache(Application $app)
    {
        $app->bind(FileCache::class, function () use ($app) {
            /** @var Path $path */
            $path = $app->make(Path::class);

            return new FileCache($app->make(FileSystemContract::class), $path->to('data/cache'));
        });
        $app->bind(CacheInterface::class, FileCache::class);
    }
}
