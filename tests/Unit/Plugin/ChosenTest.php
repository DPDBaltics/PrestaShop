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
        $module = $this->createMock('Module');
        $module->method('getLocalPath')->willReturn(self::LOCAL_PATH);
        $module->method('getPathUri')->willReturn(self::PATH_URI);

        $chosen = new Chosen($module, new Smarty());

        $this->assertSame(
            ['/js/jquery/plugins/jquery.chosen.js', self::PATH_URI . 'views/js/admin/search_block.js'],
            $chosen->getJs()
        );
    }
}
