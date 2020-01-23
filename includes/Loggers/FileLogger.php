<?php
namespace App\Loggers;

use App\System\FileSystemContract;
use App\System\Path;
use App\System\Settings;

class FileLogger
{
    /** @var Settings */
    private $settings;

    /** @var FileSystemContract */
    private $fileSystem;

    /** @var Path */
    private $path;

    public function __construct(Settings $settings, FileSystemContract $fileSystem, Path $path)
    {
        $this->settings = $settings;
        $this->fileSystem = $fileSystem;
        $this->path = $path;
    }

    public function error($message, array $data = [])
    {
        $this->log($this->path->to('data/logs/errors.log'), $message, $data);
    }

    public function info($message, array $data = [])
    {
        $this->log($this->path->to('data/logs/info.log'), $message, $data);
    }

    public function install($message, array $data = [])
    {
        $this->log($this->path->to('data/logs/install.log'), $message, $data);
    }

    private function log($file, $message, array $data = [])
    {
        $dataText = $data ? " | " . json_encode($data) : "";
        $text = date($this->settings->getDateFormat()) . ": " . $message . $dataText;

        $this->fileSystem->append($file, $text);
        $this->fileSystem->setPermissions($file, 0777);
    }
}
