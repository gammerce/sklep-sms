<?php
namespace App\Loggers;

use App\Support\FileSystemContract;
use App\Support\BasePath;
use App\System\Settings;
use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private Settings $settings;
    private FileSystemContract $fileSystem;
    private BasePath $path;

    public function __construct(Settings $settings, FileSystemContract $fileSystem, BasePath $path)
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
        $filename = $this->prepareFilename($level);
        $filePath = $this->path->to("data/logs/{$filename}.log");
        $dataText = $data ? " | " . json_encode($data) : "";
        $text = date($this->settings->getDateFormat()) . ": " . $message . $dataText;

        $this->fileSystem->append($filePath, $text);
        $this->fileSystem->setPermissions($filePath, 0777);
    }

    private function prepareFilename($level): string
    {
        $subdomain = get_identifier();
        $filename = $subdomain ? "{$subdomain}_{$level}" : $level;
        return escape_filename($filename);
    }
}
