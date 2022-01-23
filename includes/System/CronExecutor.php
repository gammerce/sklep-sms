<?php
namespace App\System;

use App\PromoCode\ExpiredSmsCodeService;
use App\Service\ExpiredUserServiceService;
use App\Support\Database;
use App\Support\FileSystemContract;
use App\Support\Path;

class CronExecutor
{
    private Database $db;
    private Settings $settings;
    private Path $path;
    private FileSystemContract $fileSystem;
    private ExpiredUserServiceService $expiredUserServiceService;
    private ExpiredSmsCodeService $expiredSmsCodeService;

    public function __construct(
        Database $db,
        Settings $settings,
        Path $path,
        FileSystemContract $fileSystem,
        ExpiredUserServiceService $expiredServiceService,
        ExpiredSmsCodeService $expiredSmsCodeService
    ) {
        $this->db = $db;
        $this->settings = $settings;
        $this->path = $path;
        $this->fileSystem = $fileSystem;
        $this->expiredUserServiceService = $expiredServiceService;
        $this->expiredSmsCodeService = $expiredSmsCodeService;
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
                ->execute([$this->settings->getDeleteLogs()]);
        }

        // Remove files older than 30 days from data/transactions
        $path = $this->path->to("data/transactions");
        foreach ($this->fileSystem->scanDirectory($path) as $file) {
            if (starts_with($file, ".")) {
                continue;
            }

            $filepath = rtrim($path, "/") . "/" . ltrim($file, "/");
            if ($this->fileSystem->lastChangedAt($filepath) < time() - 60 * 60 * 24 * 30) {
                $this->fileSystem->delete($filepath);
            }
        }
        unset($path);
    }
}
