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


namespace Invertus\dpdBaltics\Validate\CourierRequest;

use DateTime;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\DTO\CourierRequestData;
use Invertus\dpdBaltics\Util\PickupTimeSlotUtility;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CourierRequestValidator
{
    /**
     * @return bool
     */
    public function validatePickupTimeSlots(CourierRequestData $courierRequestData)
    {
        $from = $courierRequestData->getPickupTimeFrom();
        $to = $courierRequestData->getPickupTimeTo();

        if (!PickupTimeSlotUtility::isValidPickupTimeFromSlot($from)) {
            return false;
        }

        if (!PickupTimeSlotUtility::isValidPickupTimeToSlot($to)) {
            return false;
        }

        return $from < $to;
    }

    public function validate(CourierRequestData $courierRequestData, $countryIso)
    {
        $dateFrom = new DateTime($courierRequestData->getPickupTime());
        $dateFrom->modify('+' . Config::getMinimalTimeIntervalForCountry($countryIso) . ' minutes');
        $dateTo = new DateTime($courierRequestData->getSenderWorkUntil());

        if ($dateFrom->format('Y-m-d H:i:s') > $dateTo->format('Y-m-d H:i:s')) {
            return false;
        }

        return true;
    }
}
