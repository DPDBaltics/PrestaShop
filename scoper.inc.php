<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

/*
 * PrestaShop 8/9 loads the composer autoloader of every installed module, so a bundled
 * Unirest copy shares the global "Unirest" namespace with other modules and the first
 * loaded version wins. Only files that reference the Unirest namespace are scoped, and
 * only that namespace is prefixed.
 */
return [
    'prefix' => 'Invertus\\dpdBaltics\\Vendor',

    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude(['doc', 'docs', 'test', 'tests', 'Tests'])
            ->contains('/namespace\s+Unirest\b|\bUnirest\\\\/i')
            ->in(['src', 'vendor', 'controllers', 'upgrade']),
        Finder::create()
            ->files()
            ->in('.')
            ->depth(0)
            ->name('dpdbaltics.php')
            ->contains('/namespace\s+Unirest\b|\bUnirest\\\\/i'),
        Finder::create()
            ->files()
            ->in('vendor/composer')
            ->name('installed.json'),
        // Keeps the output tree rooted at the module folder even when src/ has no match.
        Finder::create()
            ->files()
            ->in('.')
            ->depth(0)
            ->name('composer.json'),
    ],

    'exclude-namespaces' => [
        '~^(?!Unirest(\\\\|$))~i',
    ],

    'exclude-files' => [
        'vendor/autoload.php',
        'vendor/composer/autoload_real.php',
        'vendor/composer/autoload_static.php',
        'vendor/composer/autoload_classmap.php',
        'vendor/composer/autoload_files.php',
        'vendor/composer/autoload_namespaces.php',
        'vendor/composer/autoload_psr4.php',
        'vendor/composer/ClassLoader.php',
        'vendor/composer/InstalledVersions.php',
        'vendor/composer/installed.php',
        'vendor/composer/platform_check.php',
    ],
];
