<?php
namespace App\Theme;

use App\Support\FileSystemContract;

class ThemeService
{
    private TemplateRepository $templateRepository;
    private FileSystemContract $fileSystem;
    private Template $template;

    public function __construct(
        TemplateRepository $templateRepository,
        FileSystemContract $fileSystem,
        Template $template
    ) {
        $this->templateRepository = $templateRepository;
        $this->fileSystem = $fileSystem;
        $this->template = $template;
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

        $template = $this->templateRepository->find(Config::DEFAULT_THEME, $name);
        if ($template) {
            return $template->getContent();
        }

        return $this->template->get($name);
    }
}
