<?php
namespace App\View\Pagination;

use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class PaginationFactory
{
    /** @var Settings */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param Request $request
     * @return Pagination
     */
    public function create(Request $request)
    {
        return new Pagination($this->settings, $request);
    }
}
