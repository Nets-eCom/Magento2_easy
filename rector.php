<?php

declare(strict_types=1);

use Magento2\Rector\Src\ReplaceMbStrposNullLimit;
use Magento2\Rector\Src\ReplaceNewDateTimeNull;
use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php80\Rector\ClassMethod\FinalPrivateToPrivateVisibilityRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\Php80\Rector\ClassMethod\SetStateToStaticRector;
use Rector\Php81\Rector\FuncCall\Php81ResourceReturnToObjectRector;
use Magento2\Rector\Src\ReplacePregSplitNullLimit;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->phpVersion(PhpVersion::PHP_74);

    $rectorConfig->sets([
        SetList::PHP_74,
        SetList::PHP_80,
        SetList::PHP_81,
        SetList::PHP_82,
    ]);

    // get services (needed for register a single rule)
    $services = $rectorConfig->services();

    // register a single rule
    $services->set(FinalPrivateToPrivateVisibilityRector::class);
    $services->set(OptionalParametersAfterRequiredRector::class);
    $services->set(SetStateToStaticRector::class);
    $services->set(StringableForToStringRector::class);
    $services->set(Php81ResourceReturnToObjectRector::class);
    $services->set(ReplacePregSplitNullLimit::class);
    $services->set(ReplaceMbStrposNullLimit::class);
    $services->set(ReplaceNewDateTimeNull::class);
    $services->set(CompleteDynamicPropertiesRector::class);
};
