<?php
namespace App\Install;

use App\Exceptions\InvalidConfigException;
use App\System\Path;
use App\System\Template;

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

        throw new InvalidConfigException($this->template->render('setup/missing_env'));
    }
}
