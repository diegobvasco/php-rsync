<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRules([
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    ->withConfiguredRule(AddOverrideAttributeToOverriddenMethodsRector::class, [
        AddOverrideAttributeToOverriddenMethodsRector::ADD_TO_INTERFACE_METHODS => true,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        codingStyle: true,
    )
    ->withPhpSets();
