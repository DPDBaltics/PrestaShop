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


namespace Invertus\dpdBaltics\Repository;

use DbQuery;
use Db;
use Invertus\dpdBalticsApi\Api\DTO\Object\ParcelShop;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ParcelShopRepository extends AbstractEntityRepository
{
    public function deleteShopsByCountryCode($countryCode)
    {
        $sql = 'DELETE w FROM `' . _DB_PREFIX_ . 'dpd_shop_work_hours` w
        INNER JOIN `' . _DB_PREFIX_ . 'dpd_shop` s ON s.country = "' . pSQL($countryCode) . '"
        WHERE s.parcel_shop_id = w.parcel_shop_id';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return $this->db->delete(
            'dpd_shop',
            '`country`= "' . pSQL($countryCode) . '"'
        );
    }

    public function getShopsByCity($country, $city)
    {
        $query = new DbQuery();
        $query->select('s.*');
        $query->from('dpd_shop', 's');
        $query->where('s.`country` = "' . pSQL($country) . '" AND s.`city` = "' . pSQL($city) . '"');

        return $this->db->executeS($query);
    }

    public function getShopsByShopId($shopId)
    {
        $query = new DbQuery();
        $query->select('s.*');
        $query->from('dpd_shop', 's');
        $query->where('s.`parcel_shop_id` = "' . pSQL($shopId) . '"');

        return $this->db->executeS($query);
    }

    public function getShopWorkHoursByShopId($parcelShopId)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('dpd_shop_work_hours', 's');
        $query->where('s.`parcel_shop_id` = "' . pSQL($parcelShopId) . '"');

        return $this->db->executeS($query);
    }

    /**
     * @param string[] $parcelShopIds
     *
     * @return array
     */
    public function getShopWorkHoursByShopIds(array $parcelShopIds)
    {
        if (empty($parcelShopIds)) {
            return [];
        }

        $escapedIds = array_map(function ($parcelShopId) {
            return '"' . pSQL($parcelShopId) . '"';
        }, array_unique($parcelShopIds));

        $query = new DbQuery();
        $query->select('*');
        $query->from('dpd_shop_work_hours', 's');
        $query->where('s.`parcel_shop_id` IN (' . implode(',', $escapedIds) . ')');
        $query->orderBy('s.`id_dpd_shop_work_hours` ASC');

        $result = $this->db->executeS($query);

        return is_array($result) ? $result : [];
    }

    public function getAllCitiesByCountryCode($countryCode)
    {
        $query = new DbQuery();
        $query->select('city');
        $query->from('dpd_shop');
        $query->where('`country` = "' . pSQL($countryCode) . '"');
        $query->groupBy('city');
        $result = $this->db->query($query);

        $cityList = [];
        while ($row = $this->db->nextRow($result)) {
            $cityList[$row['city']] = $row['city'];
        }

        return $cityList;
    }

    public function getAllAddressesByCountryCodeAndCity($countryCode, $city)
    {
        $query = new DbQuery();
        $query->select('street, company');
        $query->from('dpd_shop');
        $query->where('`country` = "' . pSQL($countryCode) . '" AND `city` = "' . pSQL($city) . '"');
        $query->groupBy('street');
        $query->orderby('company ASC');
        $result = $this->db->query($query);

        $cityList = [];
        while ($row = $this->db->nextRow($result)) {
            $cityList[$row['company']] = $row['street'];
        }

        return $cityList;
    }

    public function hasAnyParcelShops($countryCode)
    {
        $query = new DbQuery();
        $query->select('parcel_shop_id');
        $query->from('dpd_shop');
        $query->where('`country` = "' . pSQL($countryCode) . '"');

        return $this->db->getValue($query);
    }

    public function getIdByCityAndStreet($city, $street)
    {
        $query = new DbQuery();
        $query->select('parcel_shop_id');
        $query->from('dpd_shop');
        $query->where('`city` = "' . pSQL($city) . '" AND `street` LIKE "%' . pSQL($street) . '%"');

        return $this->db->getValue($query);
    }

    public function getClosestPudoShops($long, $lat, $distance, $limit)
    {
        $sql = 'SELECT * FROM  (
        SELECT *, 
            (
                (
                    (
                        acos(
                            sin(( ' . pSQL($lat) . ' * pi() / 180))
                            *
                            sin(( `latitude` * pi() / 180)) + cos(( ' . pSQL($lat) . ' * pi() /180 ))
                            *
                            cos(( `latitude` * pi() / 180)) * cos((( ' . pSQL($long) . ' - `longitude`) * pi()/180)))
                    ) * 180/pi()
                ) * 60 * 1.1515 * 1.609344
            )
        as distance FROM `' . _DB_PREFIX_ . 'dpd_shop`
        ) markers
        WHERE distance <= ' . pSQL($distance) . '
        ORDER BY distance
        LIMIT ' . pSQL($limit) . '
        ';

        return $this->db->executeS($sql);
    }

    public function getFilterByAddress($countryCode, $city, $street)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('dpd_shop');
        $query->where(
            '`country` = "' . pSQL($countryCode)
            . '" AND `city` = "' . pSQL($city)
            . '" AND (`street` LIKE "%' . pSQL($street) . '%" OR `company` LIKE "%' . pSQL($street) . '%")'
        );

        return $this->db->executeS($query);
    }
}
