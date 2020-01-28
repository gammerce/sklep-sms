<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Repositories\ServerRepository;

class ServerExistsRule extends BaseRule
{
    /** @var ServerRepository */
    private $serverRepository;

    public function __construct()
    {
        parent::__construct();
        $this->serverRepository = app()->make(ServerRepository::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->serverRepository->get($value)) {
            return [$this->lang->t('no_server_id')];
        }

        return [];
    }
}
