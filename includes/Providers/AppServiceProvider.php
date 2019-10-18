<?php
namespace App\Providers;

use App\Application;
use App\Auth;
use App\Cache\FileCache;
use App\CurrentPage;
use App\Database;
use App\ExternalConfigProvider;
use App\Filesystem;
use App\Heart;
use App\License;
use App\Path;
use App\Settings;
use App\TranslationManager;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class AppServiceProvider
{
    public function register(Application $app)
    {
        $this->registerDatabase($app);
        $this->registerCache($app);

        $app->singleton(Session::class);
        $app->singleton(Heart::class);
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
