<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\System\Settings;

class DefaultSmsPlatformRule extends BaseRule
{
    private Settings $settings;

    public function __construct()
    {
        parent::__construct();
        $this->settings = app()->make(Settings::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!$value && !$this->settings->getSmsPlatformId()) {
            throw new ValidationException($this->lang->t("no_default_sms_platform"));
        }
    }

    public function acceptsEmptyValue(): bool
    {
        return true;
    }
}
