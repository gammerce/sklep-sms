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
    public function all()
    {
        if (!$this->servicesFetched) {
            $this->fetch();
        }

        return $this->services;
    }

    /**
     * @param string $serviceId
     * @return Service|null
     */
    public function get($serviceId)
    {
        if (!$this->servicesFetched) {
            $this->fetch();
        }

        return array_get($this->services, $serviceId, null);
    }

    private function fetch()
    {
        foreach ($this->serviceRepository->all() as $service) {
            $this->services[$service->getId()] = $service;
        }

        $this->servicesFetched = true;
    }
}
