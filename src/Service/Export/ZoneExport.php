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


namespace Invertus\dpdBaltics\Service\Export;

use Country;
use DPDBaltics;
use DPDZone;
use DPDZoneRange;
use Invertus\dpdBaltics\Config\Config;
use PrestaShopCollection;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ZoneExport implements ExportableInterface
{
    const FILE_NAME = 'ZoneExport';

    /**
     * @var array|int[] Zones to export
     */
    private $idZones = [];

    /**
     * @var DPDBaltics
     */
    private $module;

    /**
     * ZoneExport constructor.
     *
     * @param DPDBaltics $module
     */
    public function __construct(DPDBaltics $module)
    {
        $this->module = $module;
    }

    /**
     * Set zones that have to be exported
     *
     * @param array|int[] $idZones
     */
    public function setZonesToExport(array $idZones)
    {
        $this->idZones = $idZones;
    }

    public function getRows()
    {
        if (empty($this->idZones)) {
            return [];
        }

        $zoneCollection = new PrestaShopCollection('DPDZone');
        $zoneCollection->where('id_dpd_zone', 'in', $this->idZones);

        /** @var DPDZone[] $zones */
        $zones = $zoneCollection->getResults();

        $rows = [];

        foreach ($zones as $zone) {
            /** @var DPDZoneRange[] $zoneRanges */
            $zoneRanges = $zone->getRanges();

            if (empty($zoneRanges)) {
                continue;
            }

            foreach ($zoneRanges as $zoneRange) {
                $country = new Country($zoneRange->id_country, null);

                $rows[] = [
                    $zone->name,
                    $country->iso_code,
                    $zoneRange->include_all_zip_codes,
                    $zoneRange->zip_code_from,
                    $zoneRange->zip_code_to,
                ];
            }
        }

        return $rows;
    }

    public function getHeaders()
    {
        return [
            $this->module->l('Zone Name', self::FILE_NAME),
            $this->module->l('Country Iso', self::FILE_NAME),
            $this->module->l('All ranges', self::FILE_NAME),
            $this->module->l('Zip code from', self::FILE_NAME),
            $this->module->l('Zip code to', self::FILE_NAME),
        ];
    }

    public function getFileName()
    {
        return sprintf(Config::IMPORT_EXPORT_OPTION_ZONES.'_%s.csv', date('Y-m-d_His'));
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasErrors()
    {
        return false;
    }
}
