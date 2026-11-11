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

use Invertus\dpdBaltics\Plugin\SearchBox\Chosen;

/**
 * Runs in a separate process so the PrestaShop stubs never reach other tests.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ChosenTest extends PHPUnit_Framework_TestCase
{
    const LOCAL_PATH = '/var/www/shop-alias/modules/dpdbaltics/';
    const PATH_URI = '/modules/dpdbaltics/';

    protected function setUp()
    {
        require_once __DIR__ . '/ChosenStubs.php';
    }

    public function testSearchBlockScriptIsLoadedByUrlNotServerPath()
    {
        $module = $this->getMockBuilder('Module')
            ->disableOriginalConstructor()
            ->setMethods(['getLocalPath', 'getPathUri'])
            ->getMockForAbstractClass();
        $module->method('getLocalPath')->willReturn(self::LOCAL_PATH);
        $module->method('getPathUri')->willReturn(self::PATH_URI);

        $chosen = new Chosen($module, new Smarty());

        $this->assertContains(self::PATH_URI . 'views/js/admin/search_block.js', $chosen->getJs());
        foreach ($chosen->getJs() as $js) {
            $this->assertNotContains(self::LOCAL_PATH, $js);
        }
    }

    public function testNoAssetIsRegisteredFromServerPath()
    {
        $root = realpath(__DIR__ . '/../../../');
        $files = [$root . '/dpdbaltics.php'];
        foreach (['src', 'controllers'] as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        $offenders = [];
        foreach ($files as $file) {
            foreach (explode(';', file_get_contents($file)) as $statement) {
                if (preg_match('/(addJS|addCSS|registerJavascript|registerStylesheet|->js\[\]|->css\[\])/', $statement)
                    && preg_match('/(getLocalPath\(\)|_PS_MODULE_DIR_|local_path|__DIR__|__FILE__)/', $statement)
                ) {
                    $offenders[] = str_replace($root . '/', '', $file) . ': ' . trim(preg_replace('/\s+/', ' ', $statement));
                }
            }
        }

        $this->assertSame([], $offenders, 'Assets must be registered with getPathUri(), Media drops server paths that do not start with _PS_ROOT_DIR_');
    }
}
