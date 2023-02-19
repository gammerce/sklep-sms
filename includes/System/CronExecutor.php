<?php
namespace App\System;

use App\Loggers\FileLogger;
use App\PromoCode\ExpiredSmsCodeService;
use App\Service\ExpiredUserServiceService;
use App\Support\Database;
use App\Support\FileSystemContract;
use App\Support\Path;

class CronExecutor
{
    private Database $db;
    private ExpiredSmsCodeService $expiredSmsCodeService;
    private ExpiredUserServiceService $expiredUserServiceService;
    private FileLogger $fileLogger;
    private FileSystemContract $fileSystem;
    private Path $path;
    private Settings $settings;

    public function __construct(
        Database $db,
        ExpiredSmsCodeService $expiredSmsCodeService,
        ExpiredUserServiceService $expiredServiceService,
        FileSystemContract $fileSystem,
        FileLogger $fileLogger,
        Path $path,
        Settings $settings
    ) {
        $this->db = $db;
        $this->expiredSmsCodeService = $expiredSmsCodeService;
        $this->expiredUserServiceService = $expiredServiceService;
        $this->fileSystem = $fileSystem;
        $this->fileLogger = $fileLogger;
        $this->path = $path;
        $this->settings = $settings;
    }

    public function run(): void
    {
        $this->expiredUserServiceService->deleteExpired();
        $this->expiredSmsCodeService->deleteExpired();

        // Remove old logs
        if ($this->settings->getDeleteLogs() > 0) {
            $this->db
                ->statement(
                    "DELETE FROM `ss_logs` WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL ? DAY)"
                )
                ->bindAndExecute([$this->settings->getDeleteLogs()]);
        }

        // Remove files older than x days from data/transactions
        if ($this->settings->getDeleteLogs() > 0) {
            $path = $this->path->to("data/transactions");
            foreach ($this->fileSystem->scanDirectory($path) as $file) {
                if (str_starts_with($file, ".")) {
                    continue;
                }

                $filepath = rtrim($path, "/") . "/" . ltrim($file, "/");
                $seconds = 60 * 60 * 24 * $this->settings->getDeleteLogs();
                if ($this->fileSystem->lastChangedAt($filepath) < time() - $seconds) {
                    $this->fileSystem->delete($filepath);
                    $this->fileLogger->info("Transaction file has been removed $filepath");
                }
            }
        }
    }
}
