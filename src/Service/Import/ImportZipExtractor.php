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

use ZipArchive;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ImportZipExtractor
{
    const ALLOWED_FILE_PATTERN = '/^(settings|zones|products|price_rules|address_templates)[^\/\\\\\x00]*\.csv$/D';

    /**
     * @var string
     */
    private $baseDir;

    /**
     * @param string|null $baseDir Directory to extract into, defaults to the system temp directory
     */
    public function __construct($baseDir = null)
    {
        $this->baseDir = $baseDir ?: $this->getDefaultBaseDir();
    }

    /**
     * Extracts only the expected import CSV files into a new private directory.
     *
     * @param string $zipFile
     *
     * @return string|false Directory with the extracted files
     */
    public function extract($zipFile)
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($zipFile)) {
            return false;
        }

        $dir = rtrim($this->baseDir, '/\\') . DIRECTORY_SEPARATOR . 'dpdbaltics_import_' . bin2hex(random_bytes(8));
        if (!@mkdir($dir, 0700)) {
            $zip->close();

            return false;
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);
            if (preg_match(self::ALLOWED_FILE_PATTERN, $name)) {
                $names[] = $name;
            }
        }

        $extracted = !$names || $zip->extractTo($dir, $names);
        $zip->close();

        if (!$extracted) {
            $this->remove($dir);

            return false;
        }

        return $dir;
    }

    /**
     * @param string $dir Directory returned by extract()
     */
    public function remove($dir)
    {
        if (!$dir || !is_dir($dir)) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }

    private function getDefaultBaseDir()
    {
        $tmpDir = sys_get_temp_dir();
        if ($tmpDir && @is_dir($tmpDir) && @is_writable($tmpDir)) {
            return $tmpDir;
        }

        return _PS_CACHE_DIR_;
    }
}
