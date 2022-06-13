<?php

use Rector\Set\ValueObject\DowngradeSetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig) {
    $rectorConfig->parallel();
    $services = $rectorConfig->services();

    $phpVersion = getenv("PHP_VERSION");
    if (version_compare($phpVersion, "8.1") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_81);
    }
    if (version_compare($phpVersion, "8.0") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_80);
    }
    if (version_compare($phpVersion, "7.4") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_74);
    }
    if (version_compare($phpVersion, "7.3") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_73);
    }
    if (version_compare($phpVersion, "7.2") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_72);
        // It needs to be removed to keep params types in class methods
        $services->remove(Rector\DowngradePhp72\Rector\ClassMethod\DowngradeParameterTypeWideningRector::class);
    }
    if (version_compare($phpVersion, "7.1") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_71);
    }
    if (version_compare($phpVersion, "7.0") < 0) {
        $rectorConfig->import(DowngradeSetList::PHP_70);
    }
};
