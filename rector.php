<?php

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\DowngradeSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator) {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // paths to refactor; solid alternative to CLI arguments
    $parameters->set(Option::PATHS, [__DIR__ . "/includes", __DIR__ . "/tests"]);

    // Define what rule sets will be applied
    $sets = [];
    $phpVersion = getenv("PHP_VERSION");
    if (version_compare($phpVersion, "8.0") < 0) {
        $sets[] = DowngradeSetList::PHP_80;
    }
    if (version_compare($phpVersion, "7.4") < 0) {
        $sets[] = DowngradeSetList::PHP_74;
    }
    if (version_compare($phpVersion, "7.3") < 0) {
        $sets[] = DowngradeSetList::PHP_73;
    }
    if (version_compare($phpVersion, "7.2") < 0) {
        $sets[] = DowngradeSetList::PHP_72;
    }
    if (version_compare($phpVersion, "7.1") < 0) {
        $sets[] = DowngradeSetList::PHP_71;
    }

    if (empty($sets)) {
        exit(0);
    }

    $parameters->set(Option::SETS, $sets);
    $parameters->set(Option::PHP_VERSION_FEATURES, $phpVersion);
};
