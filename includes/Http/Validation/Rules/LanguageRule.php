<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Support\FileSystemContract;
use App\Support\Path;

class LanguageRule extends BaseRule
{
    private FileSystemContract $fileSystem;
    private Path $path;

    public function __construct()
    {
        parent::__construct();
        $this->fileSystem = app()->make(FileSystemContract::class);
        $this->path = app()->make(Path::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        if (
            !$this->fileSystem->isDirectory($this->path->to("translations/$value")) ||
            $value[0] === "."
        ) {
            throw new ValidationException($this->lang->t("no_language"));
        }
    }
}
