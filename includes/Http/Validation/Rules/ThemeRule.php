<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Theme\ThemeRepository;

class ThemeRule extends BaseRule
{
    private ThemeRepository $themeService;

    public function __construct()
    {
        parent::__construct();
        $this->themeService = app()->make(ThemeRepository::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!$this->themeService->exists($value) || $value[0] === ".") {
            throw new ValidationException($this->lang->t("no_theme"));
        }
    }
}
