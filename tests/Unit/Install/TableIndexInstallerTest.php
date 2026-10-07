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

use Invertus\dpdBaltics\Install\TableIndexInstaller;

class TableIndexInstallerFakeDb
{
    public $indexes = [];
    public $executed = [];
    public $failOn = null;
    public $unreadable = null;
    public $cacheFlags = [];

    public function executeS($sql, $array = true, $useCache = true)
    {
        $this->cacheFlags[] = $useCache;
        if (!preg_match('/SHOW INDEX FROM `([^`]+)`/', $sql, $matches)) {
            return [];
        }
        if ($matches[1] === $this->unreadable) {
            return false;
        }

        $rows = [];
        foreach ($this->indexes as $table => $indexes) {
            if ($table !== $matches[1]) {
                continue;
            }
            foreach ($indexes as $name => $columns) {
                foreach (array_values($columns) as $position => $column) {
                    $rows[] = ['Key_name' => $name, 'Seq_in_index' => (string) ($position + 1), 'Column_name' => $column];
                }
            }
        }

        return $rows;
    }

    public function execute($sql, $useCache = true)
    {
        $this->cacheFlags[] = $useCache;
        $this->executed[] = $sql;

        return $sql !== $this->failOn;
    }
}

class TableIndexInstallerTest extends PHPUnit_Framework_TestCase
{
    public function testFreshInstallOn3xGetsEveryMissingIndex()
    {
        $db = new TableIndexInstallerFakeDb();
        $db->indexes = [
            'ps_dpd_shop' => ['PRIMARY' => ['id_dpd_shop'], 'id_dpd_shop' => ['id_dpd_shop', 'parcel_shop_id']],
            'ps_dpd_shop_work_hours' => [
                'PRIMARY' => ['id_dpd_shop_work_hours'],
                'id_dpd_shop_work_hours' => ['id_dpd_shop_work_hours', 'parcel_shop_id'],
            ],
            'ps_dpd_pudo_cart' => ['PRIMARY' => ['id'], 'id' => ['id', 'id_cart', 'id_carrier']],
            'ps_dpd_order_phone' => [
                'PRIMARY' => ['id_dpd_order_phone'],
                'id_dpd_order_phone' => ['id_dpd_order_phone', 'id_cart'],
            ],
            'ps_dpd_zone_range' => [
                'PRIMARY' => ['id_dpd_zone_range'],
                'id_dpd_zone_range' => ['id_dpd_zone_range', 'id_dpd_zone', 'id_country'],
                'id_dpd_zone' => ['id_dpd_zone', 'id_country', 'zip_code_from', 'zip_code_to'],
            ],
        ];

        $this->assertTrue((new TableIndexInstaller($db, 'ps_'))->addMissingIndexes());

        $this->assertSame([
            'ALTER TABLE `ps_dpd_shop` ADD INDEX (`parcel_shop_id`)',
            'ALTER TABLE `ps_dpd_shop` ADD INDEX (`country`, `city`)',
            'ALTER TABLE `ps_dpd_shop_work_hours` ADD INDEX (`parcel_shop_id`)',
            'ALTER TABLE `ps_dpd_pudo_cart` ADD INDEX (`id_cart`)',
            'ALTER TABLE `ps_dpd_pudo_cart` ADD INDEX (`id_carrier`)',
            'ALTER TABLE `ps_dpd_order_phone` ADD INDEX (`id_cart`)',
            'ALTER TABLE `ps_dpd_zone_range` ADD INDEX (`id_country`)',
        ], $db->executed);
    }

    public function testShopUpgradedThrough3222OnlyGetsCountryCity()
    {
        $db = new TableIndexInstallerFakeDb();
        $db->indexes = [
            'ps_dpd_shop' => [
                'PRIMARY' => ['id_dpd_shop'],
                'id_dpd_shop' => ['id_dpd_shop', 'parcel_shop_id'],
                'parcel_shop_id_2' => ['parcel_shop_id'],
            ],
            'ps_dpd_shop_work_hours' => ['parcel_shop_id' => ['parcel_shop_id']],
            'ps_dpd_pudo_cart' => ['id_cart' => ['id_cart'], 'id_carrier' => ['id_carrier']],
            'ps_dpd_order_phone' => ['id_cart' => ['id_cart']],
            'ps_dpd_zone_range' => ['id_dpd_zone_2' => ['id_dpd_zone'], 'id_country' => ['id_country']],
        ];

        $this->assertTrue((new TableIndexInstaller($db, 'ps_'))->addMissingIndexes());

        $this->assertSame(['ALTER TABLE `ps_dpd_shop` ADD INDEX (`country`, `city`)'], $db->executed);
    }

    public function testCompositeIndexIsOnlyMatchedByItsLeadingColumnsInOrder()
    {
        $db = new TableIndexInstallerFakeDb();
        $db->indexes = [
            'ps_dpd_shop' => [
                'parcel_shop_id' => ['parcel_shop_id'],
                'country' => ['country'],
                'city_country' => ['city', 'country'],
            ],
            'ps_dpd_shop_work_hours' => ['parcel_shop_id' => ['parcel_shop_id']],
            'ps_dpd_pudo_cart' => ['id_cart' => ['id_cart'], 'id_carrier' => ['id_carrier']],
            'ps_dpd_order_phone' => ['id_cart' => ['id_cart']],
            'ps_dpd_zone_range' => ['id_country' => ['id_country']],
        ];

        $this->assertTrue((new TableIndexInstaller($db, 'ps_'))->addMissingIndexes());

        $this->assertSame(['ALTER TABLE `ps_dpd_shop` ADD INDEX (`country`, `city`)'], $db->executed);
    }

    public function testLongerIndexStartingWithWantedColumnsCounts()
    {
        $db = new TableIndexInstallerFakeDb();
        $db->indexes = [
            'ps_dpd_shop' => ['parcel_shop_id' => ['parcel_shop_id'], 'loc' => ['country', 'city', 'street']],
            'ps_dpd_shop_work_hours' => ['parcel_shop_id' => ['parcel_shop_id', 'week_day']],
            'ps_dpd_pudo_cart' => ['cart_carrier' => ['id_cart', 'id_carrier'], 'id_carrier' => ['id_carrier']],
            'ps_dpd_order_phone' => ['id_cart' => ['id_cart']],
            'ps_dpd_zone_range' => ['id_country' => ['id_country']],
        ];

        $this->assertTrue((new TableIndexInstaller($db, 'ps_'))->addMissingIndexes());

        $this->assertSame([], $db->executed);
    }

    public function testStopsAndReportsFailureWhenAnIndexCannotBeAdded()
    {
        $db = new TableIndexInstallerFakeDb();
        $db->failOn = 'ALTER TABLE `ps_dpd_shop` ADD INDEX (`parcel_shop_id`)';

        $this->assertFalse((new TableIndexInstaller($db, 'ps_'))->addMissingIndexes());
        $this->assertSame([$db->failOn], $db->executed);
    }

    public function testStopsWithoutAlteringWhenIndexesCannotBeRead()
    {
        $db = new TableIndexInstallerFakeDb();
        $db->unreadable = 'ps_dpd_shop';

        $this->assertFalse((new TableIndexInstaller($db, 'ps_'))->addMissingIndexes());
        $this->assertSame([], $db->executed);
    }

    public function testBypassesSqlCacheSoARetryAfterPartialFailureSeesFreshIndexes()
    {
        $db = new TableIndexInstallerFakeDb();

        (new TableIndexInstaller($db, 'ps_'))->addMissingIndexes();

        $this->assertNotEmpty($db->cacheFlags);
        $this->assertSame([false], array_values(array_unique($db->cacheFlags)));
    }

    public function testAddsOnlyTheMissingIndexOfATableAndUsesTheGivenPrefix()
    {
        $db = new TableIndexInstallerFakeDb();
        $db->indexes = [
            'ps2_dpd_shop' => ['parcel_shop_id' => ['parcel_shop_id'], 'country' => ['country', 'city']],
            'ps2_dpd_shop_work_hours' => ['parcel_shop_id' => ['parcel_shop_id']],
            'ps2_dpd_pudo_cart' => ['id_cart' => ['id_cart']],
            'ps2_dpd_order_phone' => ['id_cart' => ['id_cart']],
            'ps2_dpd_zone_range' => ['id_country' => ['id_country']],
        ];

        $this->assertTrue((new TableIndexInstaller($db, 'ps2_'))->addMissingIndexes());

        $this->assertSame(['ALTER TABLE `ps2_dpd_pudo_cart` ADD INDEX (`id_carrier`)'], $db->executed);
    }

    /**
     * @dataProvider installSqlIndexProvider
     */
    public function testInstallSqlCreatesLeadingColumnIndexes($file, $index)
    {
        $sql = file_get_contents(__DIR__ . '/../../../sql/install/' . $file);

        $this->assertContains($index, $sql);
    }

    public function installSqlIndexProvider()
    {
        return [
            ['shop.sql', 'INDEX (`parcel_shop_id`)'],
            ['shop.sql', 'INDEX (`country`, `city`)'],
            ['shop_work_hours.sql', 'INDEX (`parcel_shop_id`)'],
            ['pudo_cart.sql', 'INDEX (`id_cart`)'],
            ['pudo_cart.sql', 'INDEX (`id_carrier`)'],
            ['order_phone.sql', 'INDEX (`id_cart`)'],
            ['zone_range.sql', 'INDEX (`id_country`)'],
        ];
    }
}
