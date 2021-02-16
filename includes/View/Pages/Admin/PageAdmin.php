<?php
namespace App\View\Pages\Admin;

use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\Support\FileSystem;
use App\Support\Path;
use App\User\Permission;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

abstract class PageAdmin extends Page implements IBeLoggedMust
{
    public function getPrivilege(): Permission
    {
        return Permission::ACP();
    }

    public function getPagePath(): string
    {
        return "/admin/{$this->getId()}";
    }

    public function addScripts(Request $request): void
    {
        /** @var FileSystem $fileSystem */
        $fileSystem = app()->make(FileSystem::class);

        /** @var Path $path */
        $path = app()->make(Path::class);

        /** @var UrlGenerator $url */
        $url = app()->make(UrlGenerator::class);

        /** @var WebsiteHeader $websiteHeader */
        $websiteHeader = app()->make(WebsiteHeader::class);

        $scriptPath = "build/js/admin/pages/{$this->getId()}/";
        if ($fileSystem->exists($path->to($scriptPath))) {
            foreach ($fileSystem->scanDirectory($path->to($scriptPath)) as $file) {
                if (ends_at($file, ".js")) {
                    $websiteHeader->addScript($url->versioned($scriptPath . $file));
                }
            }
        }
    }
}
