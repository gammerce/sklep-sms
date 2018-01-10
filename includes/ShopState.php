<?php

class ShopState
{
    /** @var Database */
    protected $db;

    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function isUpToDate()
    {
        return $this->getDbVersion() === $this->getFileVersion();
    }

    public function getDbVersion()
    {
        try {
            $version = $this->db->get_column(
                "SELECT `version` FROM `" . TABLE_PREFIX . "migrations` " .
                "ORDER BY id DESC " .
                "LIMIT 1",
                'version'
            );
        } catch (SqlQueryException $exception) {
            return 30306;
        }

        if ($version === null) {
            throw new UnexpectedValueException();
        }

        return (int)$version;
    }

    public function getFileVersion()
    {
        return self::versionToInteger(VERSION);
    }

    public static function versionToInteger($version)
    {
        $exploded = explode('.', $version);

        if (count($exploded) !== 3) {
            throw new InvalidArgumentException('Invalid version');
        }

        return $exploded[0] * 10000 + $exploded[1] * 100 + $exploded[2];
    }

    public static function isInstalled()
    {
        return DBInstance::get() !== null;
    }
}