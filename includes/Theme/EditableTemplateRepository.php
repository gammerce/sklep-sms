<?php
namespace App\Theme;

use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;

class EditableTemplateRepository
{
    private ServiceRepository $serviceRepository;
    private array $cachedTemplates = [];

    public function __construct(ServiceRepository $serviceRepository)
    {
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        if (empty($this->cachedTemplates)) {
            $this->cachedTemplates = $this->listInner();
        }

        return $this->cachedTemplates;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isEditable($name): bool
    {
        return in_array($name, $this->all(), true);
    }

    private function listInner(): array
    {
        $serviceTemplates = collect($this->serviceRepository->all())
            ->filter(
                fn(Service $service) => $service->getModule() === ExtraFlagsServiceModule::MODULE_ID
            )
            ->map(fn(Service $service) => "shop/services/{$service->getId()}_desc");

        return collect(["shop/styles/general", "shop/pages/contact", "shop/pages/regulations"])
            ->extend($serviceTemplates)
            ->sort()
            ->values()
            ->all();
    }
}
