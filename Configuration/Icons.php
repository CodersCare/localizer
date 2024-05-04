<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

$path = 'EXT:localizer/Resources/Public/Icons/';

return [
    'extensionIcon' => [
        'provider' => SvgIconProvider::class,
        'source' => $path . 'module-localizer.svg',
    ],
    'module-cart' => [
        'provider' => SvgIconProvider::class,
        'source' => $path . 'module-localizer-cart.svg',
    ],
    'module-selector' => [
        'provider' => SvgIconProvider::class,
        'source' => $path . 'module-localizer-selector.svg',
    ],
    'module-settings' => [
        'provider' => SvgIconProvider::class,
        'source' => $path . 'module-localizer-settings.svg',
    ],
];

