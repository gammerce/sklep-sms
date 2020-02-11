<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\System\Heart;

class ServerExistsRule extends BaseRule
{
    /** @var Heart */
    private $heart;

    public function __construct()
    {
        parent::__construct();
        $this->heart = app()->make(Heart::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->heart->getServer($value)) {
            return [$this->lang->t('no_server_id')];
        }

        return [];
    }
}
