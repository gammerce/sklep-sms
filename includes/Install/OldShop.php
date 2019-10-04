<?php
namespace App\Install;

use App\Application;
use App\Template;

class OldShop
{
    /** @var Template */
    private $template;

    /** @var Application */
    private $app;

    public function __construct(Application $app, Template $template)
    {
        $this->template = $template;
        $this->app = $app;
    }

    public function checkForConfigFile()
    {
        if (!file_exists($this->app->path('/includes/config.php'))) {
            return;
        }

        output_page($this->template->render('install/missing_env'));
    }
}
