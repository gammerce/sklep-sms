<?php

namespace App\Middlewares;

use App\Application;
use App\LocaleService;
use App\Settings;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class SetAdminSession implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        $app->setAdminSession();

        return null;
    }
}
