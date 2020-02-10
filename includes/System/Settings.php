<?php
namespace App\System;

use App\Support\Database;
use App\Support\FileSystemContract;
use App\Support\Path;
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

    public function __construct(Path $path, Database $database, FileSystemContract $fileSystem)
    {
        $this->db = $database;
        $this->path = $path;

        $this->data = [
            'date_format' => 'Y-m-d H:i',
            'shop_url' => '',
            'theme' => 'default',
            'timezone' => 'Europe/Warsaw',
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

    public function load()
    {
        $result = $this->db->query("SELECT * FROM `ss_settings`");
        foreach ($result as $row) {
            $this->data[$row['key']] = $this->prepareValue($row['key'], $row['value']);
        }

        if (strlen($this->data['shop_url'])) {
            $this->data['shop_url'] = $this->formatShopUrl($this->data['shop_url']);
        }

        // Fallback to default theme if selected does not exist
        if (!$this->fileSystem->exists($this->path->to("themes/{$this->data['theme']}"))) {
            $this->data['theme'] = "default";
        }

        date_default_timezone_set($this->data['timezone']);
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
     * @return float
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

    /**
     * @return string
     */
    public function getTimeZone()
    {
        return $this->data['timezone'];
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
