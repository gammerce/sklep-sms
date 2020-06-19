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
    /**
     * @return Permission
     */
    public function getPrivilege()
    {
        return Permission::ACP();
    }

    /**
     * @return string
     */
    public function getPagePath()
    {
        return "/admin/{$this->getId()}";
    }

    public function addScripts(Request $request)
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
