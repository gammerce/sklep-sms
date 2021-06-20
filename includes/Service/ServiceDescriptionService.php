<?php
namespace App\Service;

use App\Theme\Config;
use App\Theme\TemplateRepository;

// TODO Think about resetting fusion
// TODO Think about editable templates

class ServiceDescriptionService
{
    private TemplateRepository $templateRepository;

    public function __construct(TemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function create($serviceId): void
    {
        $this->templateRepository->create(
            Config::DEFAULT_THEME,
            "shop/services/{$serviceId}_desc",
            ""
        );
    }
}
