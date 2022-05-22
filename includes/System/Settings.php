<?php
namespace App\System;

use App\Support\Database;
use App\Support\FileSystemContract;
use App\Support\Path;
use App\Theme\TemplateRepository;
use App\Theme\ThemeRepository;
use ArrayAccess;

class Settings implements ArrayAccess
{
    private array $data;
    private Database $db;
    private Path $path;
    private FileSystemContract $fileSystem;
    private ThemeRepository $themeRepository;

    public function __construct(
        Path $path,
        Database $database,
        FileSystemContract $fileSystem,
        ThemeRepository $themeRepository
    ) {
        $this->db = $database;
        $this->path = $path;

        $this->data = [
            "date_format" => "Y-m-d H:i",
            "language" => "polish",
            "shop_url" => "",
            "theme" => TemplateRepository::DEFAULT_THEME,
            "timezone" => "Europe/Warsaw",
        ];
        $this->fileSystem = $fileSystem;
        $this->themeRepository = $themeRepository;
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

    public function load(): void
    {
        $result = $this->db->query("SELECT * FROM `ss_settings`");
        foreach ($result as $row) {
            $this->data[$row["key"]] = $this->prepareValue($row["key"], $row["value"]);
        }

        if (strlen($this->data["shop_url"])) {
            $this->data["shop_url"] = $this->formatShopUrl($this->data["shop_url"]);
        }

        // Fallback to fusion theme if selected does not exist
        if (!$this->themeRepository->exists($this->data["theme"])) {
            $this->data["theme"] = TemplateRepository::DEFAULT_THEME;
        }

        date_default_timezone_set($this->data["timezone"]);
    }

    public function getSmsPlatformId(): ?int
    {
        return as_int(array_get($this->data, "sms_platform"));
    }

    /**
     * @return string[]
     */
    public function getTransferPlatformIds(): array
    {
        return explode_int_list(array_get($this->data, "transfer_platform"), ",");
    }

    public function getDirectBillingPlatformId(): ?int
    {
        return as_int(array_get($this->data, "direct_billing_platform"));
    }

    public function getCurrency(): ?string
    {
        return array_get($this->data, "currency");
    }

    public function getContactEmail(): ?string
    {
        return array_get($this->data, "contact");
    }

    /**
     * @return float
     */
    public function getVat()
    {
        return as_float(array_get($this->data, "vat"));
    }

    /**
     * @return string
     */
    public function getLicenseToken()
    {
        return array_get($this->data, "license_token");
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return array_get($this->data, "date_format");
    }

    /**
     * @return string
     */
    public function getTheme()
    {
        return array_get($this->data, "theme");
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return array_get($this->data, "random_key");
    }

    /**
     * @return string
     */
    public function getTimeZone()
    {
        return array_get($this->data, "timezone");
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return array_get($this->data, "language");
    }

    /**
     * @return string
     */
    public function getShopUrl()
    {
        return array_get($this->data, "shop_url");
    }

    public function getDeleteLogs(): int
    {
        return intval(array_get($this->data, "delete_logs"));
    }

    private function formatShopUrl(string $url): string
    {
        if (!str_starts_with($url, "http://") && !str_starts_with($url, "https://")) {
            $url = "http://" . $url;
        }

        return rtrim($url, "/");
    }

    private function prepareValue($key, $value)
    {
        return strlen($value) ? $value : array_get($this->data, $key, "");
    }
}
