<?php
namespace App\Loggers;

use App\Support\FileSystemContract;
use App\Support\Path;
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

    /**
     * @param string $message
     * @param mixed $data
     */
    public function error($message, $data = null)
    {
        $this->log($this->path->to('data/logs/errors.log'), $message, $data);
    }

    /**
     * @param string $message
     * @param mixed $data
     */
    public function info($message, $data = null)
    {
        $this->log($this->path->to('data/logs/info.log'), $message, $data);
    }

    /**
     * @param string $message
     * @param mixed $data
     */
    public function install($message, $data = null)
    {
        $this->log($this->path->to('data/logs/install.log'), $message, $data);
    }

    /**
     * @param string $file
     * @param string $message
     * @param mixed $data
     */
    private function log($file, $message, $data)
    {
        $dataText = $data ? " | " . json_encode($data) : "";
        $text = date($this->settings->getDateFormat()) . ": " . $message . $dataText;

        $this->fileSystem->append($file, $text);
        $this->fileSystem->setPermissions($file, 0777);
    }
}
