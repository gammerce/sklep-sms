<?php
namespace App\System;

use ArrayAccess;

class Settings implements ArrayAccess
{
    /** @var array */
    private $data;

    /** @var Database */
    private $db;

    /** @var Path */
    private $path;

    /** @var FileSystemContract */
    private $fileSystem;

    /** @var bool */
    private $loaded = false;

    public function __construct(Path $path, Database $database, FileSystemContract $fileSystem)
    {
        $this->db = $database;
        $this->path = $path;

        $this->data = [
            'date_format' => 'Y-m-d H:i',
            'theme' => 'default',
            'shop_url' => '',
        ];
        $this->fileSystem = $fileSystem;
    }

    public function get($key)
    {
        return $this->offsetGet($key);
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
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
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "settings`");
        foreach ($result as $row) {
            $this->data[$row['key']] = $this->prepareValue($row['key'], $row['value']);
        }

        if (strlen($this->data['shop_url'])) {
            $this->data['shop_url'] = $this->formatShopUrl($this->data['shop_url']);
        }

        $this->data['transactions_query'] =
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
IFNULL(ps.free, IFNULL(pt.free, 0)) AS `free`,
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

        if ($this->data['timezone']) {
            date_default_timezone_set($this->data['timezone']);
        }

        // Fallback to default theme if selected does not exist
        $this->data['theme'] = $this->fileSystem->exists(
            $this->path->to("themes/{$this->data['theme']}")
        )
            ? $this->data['theme']
            : "default";

        $this->loaded = true;
    }

    /**
     * @return string|null
     */
    public function getSmsPlatformId()
    {
        return isset($this->data["sms_platform"]) ? (int) $this->data["sms_platform"] : null;
    }

    /**
     * @return string|null
     */
    public function getTransferPlatformId()
    {
        return isset($this->data["transfer_platform"])
            ? (int) $this->data["transfer_platform"]
            : null;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->data["currency"];
    }

    /**
     * @return string
     */
    public function getContact()
    {
        return $this->data["contact"];
    }

    /**
     * @return string
     */
    public function getVat()
    {
        return (float) $this->data["vat"];
    }

    /**
     * @return string
     */
    public function getLicenseToken()
    {
        return $this->data["license_password"];
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->data["date_format"];
    }

    /**
     * @return string
     */
    public function getTheme()
    {
        return $this->data["theme"];
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->data["random_key"];
    }

    private function formatShopUrl($url)
    {
        if (!starts_with($url, "http://") && !starts_with($url, "https://")) {
            $url = "http://" . $url;
        }

        return rtrim($url, "/");
    }

    private function prepareValue($key, $value)
    {
        return strlen($value) ? $value : array_get($this->data, $key, '');
    }
}
