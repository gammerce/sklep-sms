<?php
namespace App\System;

use ArrayAccess;

class Settings implements ArrayAccess
{
    /** @var array */
    private $settings;

    /** @var Database */
    private $db;

    /** @var Path */
    private $path;

    /** @var bool */
    private $loaded = false;

    public function __construct(Path $path, Database $database)
    {
        $this->db = $database;
        $this->path = $path;

        $this->settings = [
            'date_format' => 'Y-m-d H:i',
            'theme' => 'default',
            'shop_url' => '',
        ];
    }

    public function get($key)
    {
        return $this->offsetGet($key);
    }

    public function offsetExists($offset)
    {
        return isset($this->settings[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->settings[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->settings[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->settings[$offset]);
    }

    public function loadIfNotLoaded()
    {
        if ($this->loaded) {
            return;
        }

        $this->load();
    }

    public function load()
    {
        // Pozyskanie ustawień sklepu
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "settings`");
        foreach ($result as $row) {
            $this->settings[$row['key']] = $this->prepareValue($row['key'], $row['value']);
        }

        // Poprawiamy adres URL sklepu
        if (strlen($this->settings['shop_url'])) {
            if (
                strpos($this->settings['shop_url'], "http://") !== 0 &&
                strpos($this->settings['shop_url'], "https://") !== 0
            ) {
                $this->settings['shop_url'] = "http://" . $this->settings['shop_url'];
            }

            $this->settings['shop_url'] = rtrim($this->settings['shop_url'], "/");
        }

        $this->settings['transactions_query'] =
            "(SELECT bs.id AS `id`,
bs.uid AS `uid`,
u.username AS `username`,
bs.payment AS `payment`,
bs.payment_id AS `payment_id`,
bs.service AS `service`,
bs.server AS `server`,
bs.amount AS `amount`,
bs.auth_data AS `auth_data`,
bs.email AS `email`,
bs.extra_data AS `extra_data`,
CONCAT_WS('', pa.ip, ps.ip, pt.ip, pw.ip, pc.ip) AS `ip`,
CONCAT_WS('', pa.platform, ps.platform, pt.platform, pw.platform, pc.platform) AS `platform`,
CONCAT_WS('', ps.income, pt.income) AS `income`,
CONCAT_WS('', ps.cost, pt.income, pw.cost) AS `cost`,
pa.aid AS `aid`,
u2.username AS `adminname`,
ps.code AS `sms_code`,
ps.text AS `sms_text`,
ps.number AS `sms_number`,
IFNULL(ps.free,0) AS `free`,
pc.code AS `service_code`,
bs.timestamp AS `timestamp`
FROM `" .
            TABLE_PREFIX .
            "bought_services` AS bs
LEFT JOIN `" .
            TABLE_PREFIX .
            "users` AS u ON u.uid = bs.uid
LEFT JOIN `" .
            TABLE_PREFIX .
            "payment_admin` AS pa ON bs.payment = 'admin' AND pa.id = bs.payment_id
LEFT JOIN `" .
            TABLE_PREFIX .
            "users` AS u2 ON u2.uid = pa.aid
LEFT JOIN `" .
            TABLE_PREFIX .
            "payment_sms` AS ps ON bs.payment = 'sms' AND ps.id = bs.payment_id
LEFT JOIN `" .
            TABLE_PREFIX .
            "payment_transfer` AS pt ON bs.payment = 'transfer' AND pt.id = bs.payment_id
LEFT JOIN `" .
            TABLE_PREFIX .
            "payment_wallet` AS pw ON bs.payment = 'wallet' AND pw.id = bs.payment_id
LEFT JOIN `" .
            TABLE_PREFIX .
            "payment_code` AS pc ON bs.payment = 'service_code' AND pc.id = bs.payment_id)";

        if ($this->settings['timezone']) {
            date_default_timezone_set($this->settings['timezone']);
        }

        $this->settings['date_format'] = strlen($this->settings['date_format'])
            ? $this->settings['date_format']
            : "Y-m-d H:i";

        // Fallback to default theme if selected does not exist
        $this->settings['theme'] = file_exists($this->path->to("themes/{$this->settings['theme']}"))
            ? $this->settings['theme']
            : "default";

        $this->loaded = true;
    }

    /**
     * @return string|null
     */
    public function getSmsPlatformId()
    {
        return isset($this->settings["sms_platform"])
            ? intval($this->settings["sms_platform"])
            : null;
    }

    /**
     * @return string|null
     */
    public function getTransferPlatformId()
    {
        return isset($this->settings["transfer_platform"])
            ? intval($this->settings["transfer_platform"])
            : null;
    }

    private function prepareValue($key, $value)
    {
        return strlen($value) ? $value : array_get($this->settings, $key, '');
    }
}
