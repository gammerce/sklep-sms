<?php
namespace App\Install;

use App\Path;
use App\Template;

class OldShop
{
    /** @var Template */
    private $template;

    /** @var Path */
    private $path;

    public function __construct(Path $path, Template $template)
    {
        $this->template = $template;
        $this->path = $path;
    }

    public function checkForConfigFile()
    {
        if (!file_exists($this->path->to('/includes/config.php'))) {
            return;
        }

        output_page($this->template->render('setup/missing_env'));
    }
}
