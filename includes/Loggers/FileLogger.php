<?php
namespace App\Loggers;

use App\Support\FileSystemContract;
use App\Support\Path;
use App\System\Settings;
use Psr\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private Settings $settings;
    private FileSystemContract $fileSystem;
    private Path $path;

    public function __construct(Settings $settings, FileSystemContract $fileSystem, Path $path)
    {
        $this->settings = $settings;
        $this->fileSystem = $fileSystem;
        $this->path = $path;
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->error($message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->error($message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->error($message, $context);
    }

    /**
     * @param string|\Stringable $message
     * @param array $context
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log("errors", $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->error($message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->info($message, $context);
    }

    /**
     * @param string|\Stringable $message
     * @param array $context
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log("info", $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->info($message, $context);
    }

    /**
     * @param string|\Stringable $message
     * @param array $context
     */
    public function install(string|\Stringable $message, array $context = []): void
    {
        $this->log("install", $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $filename = $this->prepareFilename($level);
        $filePath = $this->path->to("data/logs/{$filename}.log");
        $dataText = $context ? " | " . json_encode($context) : "";
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
