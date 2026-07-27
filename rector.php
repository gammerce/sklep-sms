<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\DowngradeSetList;

return static function (RectorConfig $rectorConfig) {
    $rectorConfig->parallel();

    $phpVersion = getenv("PHP_VERSION");

    if (version_compare($phpVersion, "8.4") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_84);
    }
    if (version_compare($phpVersion, "8.3") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_83);
    }
    if (version_compare($phpVersion, "8.2") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_82);
    }
    if (version_compare($phpVersion, "8.1") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_81);
    }
};
