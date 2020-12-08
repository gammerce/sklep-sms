<?php

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\DowngradeSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator) {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // paths to refactor; solid alternative to CLI arguments
    $parameters->set(Option::PATHS, [__DIR__ . "/includes", __DIR__ . "/tests", __DIR__ . "/vendor"]);

    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        DowngradeSetList::PHP_80,
        DowngradeSetList::PHP_74,
        DowngradeSetList::PHP_73,
        DowngradeSetList::PHP_72,
        DowngradeSetList::PHP_71,
    ]);

    $parameters->set(Option::PHP_VERSION_FEATURES, "5.6");
};
