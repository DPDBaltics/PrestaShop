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

use Invertus\dpdBaltics\Service\Import\LeftoverImportFilesCleaner;

class LeftoverImportFilesCleanerTest extends PHPUnit_Framework_TestCase
{
    private $dir;

    protected function setUp()
    {
        $this->dir = sys_get_temp_dir() . '/dpdbaltics_test_' . uniqid();
        mkdir($this->dir . '/sub/deeper', 0777, true);
        file_put_contents($this->dir . '/index.php', 'guard');
        file_put_contents($this->dir . '/shell.php', '<?php echo 1;');
        file_put_contents($this->dir . '/.htaccess', 'Allow from all');
        file_put_contents($this->dir . '/zones_20240101_1200.csv', 'zones');
        file_put_contents($this->dir . '/sub/index.php', 'nested');
        file_put_contents($this->dir . '/sub/deeper/shell.phtml', '<?php echo 1;');
    }

    protected function tearDown()
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    public function testRemovesEverythingExceptTopLevelIndex()
    {
        $cleaner = new LeftoverImportFilesCleaner();

        $cleaner->clean($this->dir);

        $this->assertEquals(['index.php'], array_values(array_diff(scandir($this->dir), ['.', '..'])));
        $this->assertEquals('guard', file_get_contents($this->dir . '/index.php'));
    }

    public function testIgnoresMissingDirectory()
    {
        $cleaner = new LeftoverImportFilesCleaner();

        $cleaner->clean($this->dir . '/missing');

        $this->assertFileExists($this->dir . '/index.php');
    }
}
