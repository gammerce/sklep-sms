<?php
namespace Install;

use App\Template;

class OldShop
{
    /** @var Template */
    private $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function checkForConfigFile()
    {
        if (!file_exists(SCRIPT_ROOT . '/includes/config.php')) {
            return;
        }

        $output = eval($this->template->install_render('missing_env'));
        output_page($output);
    }
}