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


namespace Invertus\dpdBaltics\Service\Import;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Removes files that older module versions extracted from import ZIPs into the module folder.
 */
class LeftoverImportFilesCleaner
{
    /**
     * Deletes everything in the directory except its own index.php.
     *
     * @param string $dir
     */
    public function clean($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $dir = rtrim($dir, '/\\');
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $path = $item->getPathname();
            if ($path === $dir . DIRECTORY_SEPARATOR . 'index.php') {
                continue;
            }

            if ($item->isDir() && !$item->isLink()) {
                @rmdir($path);
                continue;
            }

            @unlink($path);
        }
    }
}
