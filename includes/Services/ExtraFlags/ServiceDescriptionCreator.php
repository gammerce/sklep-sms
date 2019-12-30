<?php
namespace App\Services\ExtraFlags;

use App\Exceptions\InvalidConfigException;
use App\System\Path;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServiceDescriptionCreator
{
    /** @var Path */
    private $path;

    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    public function __construct(
        Path $path,
        Settings $settings,
        TranslationManager $translationManager
    ) {
        $this->path = $path;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
    }

    public function create($name)
    {
        $path = $this->path->to(
            "themes/{$this->settings['theme']}/services/" . escape_filename($name) . "_desc.html"
        );

        if (!file_exists($path)) {
            file_put_contents($path, "");

            chmod($path, 0777);

            // Check if permissions were assigned
            if (substr(sprintf('%o', fileperms($path)), -4) != "0777") {
                throw new InvalidConfigException(
                    $this->lang->sprintf(
                        $this->lang->translate('wrong_service_description_file'),
                        $this->settings['theme']
                    )
                );
            }
        }
    }
}
