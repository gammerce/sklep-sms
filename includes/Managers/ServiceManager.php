<?php
namespace App\Managers;

use App\Models\Service;
use App\Repositories\ServiceRepository;

class ServiceManager
{
    /** @var ServiceRepository */
    private $serviceRepository;

    /** @var Service[] */
    private $services = [];
    private $servicesFetched = false;

    public function __construct(ServiceRepository $serviceRepository)
    {
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Returns purchasable services
     *
     * @return Service[]
     */
    public function getServices()
    {
        if (!$this->servicesFetched) {
            $this->fetchServices();
        }

        return $this->services;
    }

    /**
     * @param $serviceId
     * @return Service|null
     */
    public function getService($serviceId)
    {
        if (!$this->servicesFetched) {
            $this->fetchServices();
        }

        return array_get($this->services, $serviceId, null);
    }

    private function fetchServices()
    {
        foreach ($this->serviceRepository->all() as $service) {
            $this->services[$service->getId()] = $service;
        }

        $this->servicesFetched = true;
    }
}