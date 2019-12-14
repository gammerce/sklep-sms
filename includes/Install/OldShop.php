<?php
namespace App\Install;

use App\System\Path;

class OldShop
{
    /** @var Path */
    private $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function hasConfigFile()
    {
        return file_exists($this->path->to('/includes/config.php'));
    }
}
