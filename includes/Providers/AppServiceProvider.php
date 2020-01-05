<?php
namespace App\Providers;

use App\Cache\FileCache;
use App\System\Application;
use App\System\Auth;
use App\System\CurrentPage;
use App\System\Database;
use App\System\ExternalConfigProvider;
use App\System\Filesystem;
use App\System\License;
use App\System\Path;
use App\System\Settings;
use App\Translation\TranslationManager;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class AppServiceProvider
{
    public function register(Application $app)
    {
        $this->registerDatabase($app);
        $this->registerCache($app);

        $app->singleton(Session::class);
        $app->singleton(Auth::class);
        $app->singleton(Settings::class);
        $app->singleton(CurrentPage::class);
        $app->singleton(License::class);
        $app->singleton(TranslationManager::class);
        $app->singleton(ExternalConfigProvider::class);
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

            return new FileCache($app->make(Filesystem::class), $path->to('data/cache'));
        });
        $app->bind(CacheInterface::class, FileCache::class);
    }
}
