<?php
namespace App;

class CronExecutor
{
    /** @var Application */
    protected $app;

    /** @var Database */
    protected $db;

    /** @var Settings */
    protected $settings;

    public function __construct(Application $application, Database $db, Settings $settings)
    {
        $this->app = $application;
        $this->db = $db;
        $this->settings = $settings;
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
        $path = $this->app->path('data/transfers');
        foreach (scandir($path) as $file) {
            $filepath = rtrim($path, "/") . "/" . ltrim($file, "/");
            if (filectime($filepath) < time() - 60 * 60 * 24 * 30) {
                unlink($filepath);
            }
        }
        unset($path);
    }
}
