<?php
namespace App\View\Pages;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Managers\PageManager;
use App\System\Auth;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Admin\PageAdmin;

class PageResolver
{
    private Auth $auth;
    private PageManager $pageManager;

    public function __construct(Auth $auth, PageManager $pageManager)
    {
        $this->auth = $auth;
        $this->pageManager = $pageManager;
    }

    /**
     * @param PageAdmin|string $page
     * @return PageAdmin
     * @throws ForbiddenException
     * @throws UnauthorizedException
     * @throws EntityNotFoundException
     */
    public function resolveAdmin($page)
    {
        if (!($page instanceof PageAdmin)) {
            $page = $this->pageManager->getAdmin($page);
        }

        if (!$page) {
            throw new EntityNotFoundException();
        }

        if ($this->auth->user()->cannot($page->getPrivilege())) {
            throw new ForbiddenException();
        }

        return $this->resolve($page);
    }

    /**
     * @param Page|string $page
     * @return Page
     * @throws ForbiddenException
     * @throws UnauthorizedException
     * @throws EntityNotFoundException
     */
    public function resolveUser($page)
    {
        if (!($page instanceof Page)) {
            $page = $this->pageManager->getUser($page);
        }

        if (!$page) {
            throw new EntityNotFoundException();
        }

        return $this->resolve($page);
    }

    /**
     * @param Page $page
     * @return Page
     * @throws ForbiddenException
     * @throws UnauthorizedException
     */
    private function resolve(Page $page)
    {
        if ($page instanceof IBeLoggedMust && !$this->auth->check()) {
            throw new UnauthorizedException();
        }

        if ($page instanceof IBeLoggedCannot && $this->auth->check()) {
            throw new ForbiddenException();
        }

        return $page;
    }
}
