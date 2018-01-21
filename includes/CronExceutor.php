<?php
namespace App;

use Database;

class CronExceutor
{
    /** @var Database */
    protected $db;

    /** @var Settings */
    protected $settings;

    public function __construct(Database $db, Settings $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    public function run()
    {
        // Pozyskujemy wszystkie klasy implementujące interface cronjob
        $classes = array_filter(
            get_declared_classes(),
            function ($className) {
                return in_array('I_Cronjob', class_implements($className));
            }
        );

        foreach ($classes as $class) {
            $class::cronjob_pre();
        }

        // Usuwamy przestarzałe usługi użytkowników
        delete_users_old_services();

        // Usuwamy przestarzałe logi
        if (intval($this->settings['delete_logs']) != 0) {
            $this->db->query($this->db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "logs` " .
                "WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL '%d' DAY)",
                [$this->settings['delete_logs']]
            ));
        }

        // Remove files older than 30 days from data/transfers
        $path = SCRIPT_ROOT . "data/transfers";
        foreach (scandir($path) as $file) {
            if (filectime($path . $file) < time() - 60 * 60 * 24 * 30) {
                unlink($path . $file);
            }
        }
        unset($path);

        foreach ($classes as $class) {
            $class::cronjob_post();
        }
    }
}