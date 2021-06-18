<?php
namespace App\Theme;

use App\Repositories\TemplateRepository;
use App\Support\FileSystemContract;

class ThemeService
{
    private TemplateRepository $templateRepository;
    private FileSystemContract $fileSystem;

    public function __construct(
        TemplateRepository $templateRepository,
        FileSystemContract $fileSystem
    ) {
        $this->templateRepository = $templateRepository;
        $this->fileSystem = $fileSystem;
    }

    public function getEditableTemplates(): array
    {
        return ["styles", "shop/pages/contact", "shop/pages/regulations"];
    }

    public function resolveTemplate($name): string
    {
        return str_replace("-", "/", $name);
    }

    public function getTemplateContent($theme, $name): string
    {
        $template = $this->templateRepository->find($theme, $name);
        if ($template) {
            return $template->getContent();
        }

        $template = $this->templateRepository->find("fusion", $name);
        if ($template) {
            return $template->getContent();
        }

        return $this->fileSystem->get("themes/fusion/$name.html");
    }
}
