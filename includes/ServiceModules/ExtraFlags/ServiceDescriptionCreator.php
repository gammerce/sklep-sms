<?php
namespace App\ServiceModules\ExtraFlags;

use App\Exceptions\InvalidConfigException;
use App\System\FileSystemContract;
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

    /** @var FileSystemContract */
    private $fileSystem;

    public function __construct(
        Path $path,
        Settings $settings,
        TranslationManager $translationManager,
        FileSystemContract $fileSystem
    ) {
        $this->path = $path;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->fileSystem = $fileSystem;
    }

    public function create($name)
    {
        $path = $this->path->to(
            "themes/{$this->settings['theme']}/services/" . escape_filename($name) . "_desc.html"
        );

        if (!$this->fileSystem->exists($path)) {
            $this->fileSystem->put($path, "");
            $this->fileSystem->setPermissions($path, 0777);

            // Check if permissions were assigned
            if (substr(sprintf('%o', $this->fileSystem->getPermissions($path)), -4) != "0777") {
                throw new InvalidConfigException(
                    $this->lang->t('wrong_service_description_file', $this->settings['theme'])
                );
            }
        }
    }
}
