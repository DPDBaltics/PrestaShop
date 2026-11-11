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


namespace Invertus\dpdBaltics\Factory;

use Invertus\dpdBaltics\Repository\ParcelShopRepository;
use Invertus\dpdBalticsApi\Api\DTO\Object\OpeningHours;
use Invertus\dpdBalticsApi\Api\DTO\Object\ParcelShop;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShopFactory
{
    /**
     * @var ParcelShopRepository
     */
    private $parcelShopRepository;

    public function __construct(ParcelShopRepository $parcelShopRepository)
    {
        $this->parcelShopRepository = $parcelShopRepository;
    }

    /**
     * @param array $shops
     * @return array
     */
    public function createShop(array $shops)
    {
        $shopsArray = [];
        $openingHoursByShopId = $this->getOpeningHoursByShopId($shops);
        foreach ($shops as $shop) {
            $workHoursArray = isset($openingHoursByShopId[$shop['parcel_shop_id']])
                ? $openingHoursByShopId[$shop['parcel_shop_id']]
                : [];

            $shopObj = new ParcelShop();
            $shopObj->setParcelShopId($shop['parcel_shop_id']);
            $shopObj->setCompany($shop['company']);
            $shopObj->setCountry($shop['country']);
            $shopObj->setCity($shop['city']);
            $shopObj->setPCode($shop['p_code']);
            $shopObj->setStreet($shop['street']);
            $shopObj->setEmail($shop['email']);
            $shopObj->setLongitude($shop['longitude']);
            $shopObj->setLatitude($shop['latitude']);
            $shopObj->setOpeningHours($workHoursArray);

            $shopsArray[] = $shopObj;
        }

        return $shopsArray;
    }

    private function getOpeningHoursByShopId(array $shops)
    {
        if (empty($shops)) {
            return [];
        }

        $workHours = $this->parcelShopRepository->getShopWorkHoursByShopIds(
            array_column($shops, 'parcel_shop_id')
        );

        $openingHoursByShopId = [];
        foreach ($workHours as $workHour) {
            $openingHoursByShopId[$workHour['parcel_shop_id']][] = new OpeningHours(
                $workHour['week_day'],
                $workHour['open_morning'],
                $workHour['close_morning'],
                $workHour['close_afternoon'],
                $workHour['open_afternoon']
            );
        }

        return $openingHoursByShopId;
    }
}