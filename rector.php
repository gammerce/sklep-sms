<?php

use Rector\Set\ValueObject\DowngradeSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator) {
    $phpVersion = getenv("PHP_VERSION");
    if (version_compare($phpVersion, "8.0") < 0) {
        $containerConfigurator->import(DowngradeSetList::PHP_80);
    }
    if (version_compare($phpVersion, "7.4") < 0) {
        $containerConfigurator->import(DowngradeSetList::PHP_74);
    }
    if (version_compare($phpVersion, "7.3") < 0) {
        $containerConfigurator->import(DowngradeSetList::PHP_73);
    }
    if (version_compare($phpVersion, "7.2") < 0) {
        $containerConfigurator->import(DowngradeSetList::PHP_72);
    }
    if (version_compare($phpVersion, "7.1") < 0) {
        $containerConfigurator->import(DowngradeSetList::PHP_71);
    }
    if (version_compare($phpVersion, "7.0") < 0) {
        $containerConfigurator->import(DowngradeSetList::PHP_70);
    }
};
