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

class NamespaceCaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Composer resolves namespaces case-sensitively, so a wrongly cased reference
     * fatals the class even though PHP class names are case-insensitive.
     */
    public function testModuleNamespaceIsReferencedWithExactCase()
    {
        $moduleDir = __DIR__ . '/../../../';
        $files = array_merge(
            [$moduleDir . 'dpdbaltics.php'],
            glob($moduleDir . 'controllers/*/*.php'),
            glob($moduleDir . 'upgrade/*.php'),
            $this->getPhpFiles($moduleDir . 'src')
        );

        foreach ($files as $file) {
            preg_match_all('/Invertus\\\\+(dpdbaltics(?:api)?)\\\\/i', file_get_contents($file), $matches);

            foreach ($matches[1] as $rootNamespace) {
                $this->assertContains(
                    $rootNamespace,
                    ['dpdBaltics', 'dpdBalticsApi'],
                    str_replace($moduleDir, '', $file) . ' references Invertus\\' . $rootNamespace
                );
            }
        }
    }

    private function getPhpFiles($dir)
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
