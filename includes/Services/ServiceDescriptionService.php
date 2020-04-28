<?php
namespace App\Services;

use App\Support\FileSystemContract;
use App\Support\Path;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServiceDescriptionService
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

    public function create($serviceId)
    {
        $path = $this->path->to(
            "themes/{$this->settings->getTheme()}/{$this->getTemplatePath($serviceId)}"
        );

        if (!$this->fileSystem->exists($path)) {
            $this->fileSystem->put($path, "");
            $this->fileSystem->setPermissions($path, 0777);
        }
    }

    public function getTemplatePath($serviceId)
    {
        $escapedName = escape_filename($serviceId);
        return "/shop/services/{$escapedName}_desc.html";
    }
}
