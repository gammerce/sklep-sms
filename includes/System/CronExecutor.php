<?php
namespace App\System;

class CronExecutor
{
    /** @var Database */
    private $db;

    /** @var Settings */
    private $settings;

    /** @var Path */
    private $path;

    /** @var FileSystemContract */
    private $fileSystem;

    public function __construct(
        Database $db,
        Settings $settings,
        Path $path,
        FileSystemContract $fileSystem
    ) {
        $this->db = $db;
        $this->settings = $settings;
        $this->path = $path;
        $this->fileSystem = $fileSystem;
    }

    public function run()
    {
        // Usuwamy przestarzałe usługi użytkowników
        delete_users_old_services();

        // Usuwamy przestarzałe logi
        if (intval($this->settings['delete_logs']) != 0) {
            $this->db->query(
                $this->db->prepare(
                    "DELETE FROM `" .
                        TABLE_PREFIX .
                        "logs` " .
                        "WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL '%d' DAY)",
                    [$this->settings['delete_logs']]
                )
            );
        }

        // Remove files older than 30 days from data/transfers
        $path = $this->path->to('data/transfers');
        foreach ($this->fileSystem->scanDirectory($path) as $file) {
            $filepath = rtrim($path, "/") . "/" . ltrim($file, "/");
            if (filectime($filepath) < time() - 60 * 60 * 24 * 30) {
                $this->fileSystem->delete($filepath);
            }
        }
        unset($path);
    }
}
