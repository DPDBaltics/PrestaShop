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

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.9');
}

use Invertus\dpdBaltics\Service\Import\ImportZipExtractor;

class ImportZipExtractorTest extends PHPUnit_Framework_TestCase
{
    private $workDir;

    protected function setUp()
    {
        $this->workDir = sys_get_temp_dir() . '/dpdbaltics_test_' . uniqid();
        mkdir($this->workDir . '/base', 0777, true);
    }

    protected function tearDown()
    {
        exec('rm -rf ' . escapeshellarg($this->workDir));
    }

    public function testExtractsOnlyExpectedCsvFilesOutsideModuleFolder()
    {
        $zipFile = $this->createZip([
            'settings_20240101_1200.csv' => 'settings',
            'zones_20240101_1200.csv' => 'zones',
            'products.csv' => 'products',
            'zones (1).csv' => 'renamed zones',
            'price_rules_20240101_1200.csv' => 'price rules',
            'address_templates_20240101_1200.csv' => 'addresses',
            'shell.php' => '<?php echo 1;',
            'shell.phtml' => '<?php echo 1;',
            'zones_shell.php' => '<?php echo 1;',
            'zones_x.csv.php' => '<?php echo 1;',
            'notes.txt' => 'notes',
            '.htaccess' => 'Allow from all',
            'sub/zones_nested.csv' => 'nested',
            '../zones_escape.csv' => 'escape',
            'zones\\windows.csv' => 'backslash',
            'evil.csv' => 'unknown type',
        ]);

        $extractor = new ImportZipExtractor($this->workDir . '/base');
        $dir = $extractor->extract($zipFile);

        $this->assertStringStartsWith($this->workDir . '/base/', $dir);
        $this->assertEquals(
            [
                'address_templates_20240101_1200.csv',
                'price_rules_20240101_1200.csv',
                'products.csv',
                'settings_20240101_1200.csv',
                'zones (1).csv',
                'zones_20240101_1200.csv',
            ],
            $this->listFiles($dir)
        );
        $this->assertEquals('zones', file_get_contents($dir . '/zones_20240101_1200.csv'));
        $this->assertFileNotExists($this->workDir . '/zones_escape.csv');
    }

    public function testEveryExtractionGetsItsOwnDirectory()
    {
        $zipFile = $this->createZip(['zones.csv' => 'zones']);
        $extractor = new ImportZipExtractor($this->workDir . '/base');

        $this->assertNotEquals($extractor->extract($zipFile), $extractor->extract($zipFile));
    }

    public function testReturnsFalseForInvalidZip()
    {
        $file = $this->workDir . '/broken.zip';
        file_put_contents($file, 'not a zip');
        $extractor = new ImportZipExtractor($this->workDir . '/base');

        $this->assertFalse($extractor->extract($file));
        $this->assertEquals([], $this->listFiles($this->workDir . '/base'));
    }

    public function testReturnsFalseWhenDirectoryCannotBeCreated()
    {
        $zipFile = $this->createZip(['zones.csv' => 'zones']);
        $extractor = new ImportZipExtractor($this->workDir . '/missing/base');

        $this->assertFalse($extractor->extract($zipFile));
    }

    public function testRejectsNamesWithTrailingNewline()
    {
        $this->assertSame(0, preg_match(ImportZipExtractor::ALLOWED_FILE_PATTERN, "zones.csv\n"));
        $this->assertSame(0, preg_match(ImportZipExtractor::ALLOWED_FILE_PATTERN, "zones\0.csv"));
        $this->assertSame(1, preg_match(ImportZipExtractor::ALLOWED_FILE_PATTERN, 'zones (1).csv'));
    }

    public function testRemoveDeletesExtractedDirectory()
    {
        $zipFile = $this->createZip(['zones.csv' => 'zones', 'settings.csv' => 'settings']);
        $extractor = new ImportZipExtractor($this->workDir . '/base');
        $dir = $extractor->extract($zipFile);

        $extractor->remove($dir);

        $this->assertFileNotExists($dir);
    }

    private function createZip(array $entries)
    {
        $zipFile = $this->workDir . '/import.zip';
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $zipFile;
    }

    private function listFiles($dir)
    {
        $files = array_values(array_diff(scandir($dir), ['.', '..']));
        sort($files);

        return $files;
    }
}
