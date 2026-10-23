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


namespace Invertus\dpdBaltics\Install;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Adds the lookup indexes that shops installed or reset since 3.2.22 are missing.
 * An index counts as present when its leading columns match, whatever its name,
 * so shops that already ran Upgrade-3.2.22 get no duplicates.
 */
class TableIndexInstaller
{
    const INDEXES = [
        ['dpd_shop', ['parcel_shop_id']],
        ['dpd_shop', ['country', 'city']],
        ['dpd_shop_work_hours', ['parcel_shop_id']],
        ['dpd_pudo_cart', ['id_cart']],
        ['dpd_pudo_cart', ['id_carrier']],
        ['dpd_order_phone', ['id_cart']],
        ['dpd_zone_range', ['id_country']],
    ];

    /**
     * @var \Db
     */
    private $db;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param \Db $db
     * @param string $prefix
     */
    public function __construct($db, $prefix)
    {
        $this->db = $db;
        $this->prefix = $prefix;
    }

    /**
     * @return bool
     */
    public function addMissingIndexes()
    {
        foreach (self::INDEXES as list($table, $columns)) {
            $indexes = $this->getIndexColumns($table);
            if ($indexes === null) {
                return false;
            }

            if ($this->hasIndexStartingWith($indexes, $columns)) {
                continue;
            }

            $sql = sprintf(
                'ALTER TABLE `%s` ADD INDEX (`%s`)',
                $this->prefix . $table,
                implode('`, `', $columns)
            );

            if (!$this->db->execute($sql, false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reads the table indexes without the PS SQL cache, which ALTER TABLE does not invalidate.
     *
     * @param string $table
     *
     * @return array<string, string[]>|null column names per index, or null when the table cannot be read
     */
    private function getIndexColumns($table)
    {
        $rows = $this->db->executeS('SHOW INDEX FROM `' . $this->prefix . $table . '`', true, false);
        if (!is_array($rows)) {
            return null;
        }

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row['Key_name']][(int) $row['Seq_in_index']] = $row['Column_name'];
        }

        foreach ($indexes as $name => $indexColumns) {
            ksort($indexColumns);
            $indexes[$name] = array_values($indexColumns);
        }

        return $indexes;
    }

    /**
     * @param array<string, string[]> $indexes
     * @param string[] $columns
     *
     * @return bool
     */
    private function hasIndexStartingWith(array $indexes, array $columns)
    {
        foreach ($indexes as $indexColumns) {
            if (array_slice($indexColumns, 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }
}
