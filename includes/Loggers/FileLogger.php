<?php
namespace App\Loggers;

use App\Support\FileSystemContract;
use App\Support\Path;
use App\System\Settings;
use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface
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
     * @param array $context
     */
    public function error($message, array $context = [])
    {
        $this->log("errors", $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        $this->log("info", $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function install($message, array $context = [])
    {
        $this->log("install", $message, $context);
    }

    public function emergency($message, array $context = [])
    {
        $this->error($message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->error($message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->error($message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->error($message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->info($message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->info($message, $context);
    }

    /**
     * @param string $level
     * @param string $message
     * @param mixed $data
     */
    public function log($level, $message, array $data = [])
    {
        $filename = escape_filename($level);
        $filePath = $this->path->to("data/logs/{$filename}.log");
        $dataText = $data ? " | " . json_encode($data) : "";
        $text = date($this->settings->getDateFormat()) . ": " . $message . $dataText;

        $this->fileSystem->append($filePath, $text);
        $this->fileSystem->setPermissions($filePath, 0777);
    }
}
