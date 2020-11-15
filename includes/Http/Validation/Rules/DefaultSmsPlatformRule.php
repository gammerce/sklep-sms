<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Http\Validation\EmptyRule;
use App\System\Settings;

class DefaultSmsPlatformRule extends BaseRule implements EmptyRule
{
    /** @var Settings */
    private $settings;

    public function __construct()
    {
        parent::__construct();
        $this->settings = app()->make(Settings::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$value && !$this->settings->getSmsPlatformId()) {
            return [$this->lang->t("no_default_sms_platform")];
        }

        return [];
    }
}
