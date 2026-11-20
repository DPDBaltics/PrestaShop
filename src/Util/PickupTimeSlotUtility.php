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


namespace Invertus\dpdBaltics\Util;

use Invertus\dpdBaltics\Config\Config;

class PickupTimeSlotUtility
{
    /**
     * @return string[]
     */
    public static function getPickupTimeFromSlots()
    {
        return Config::COURIER_PICKUP_TIME_FROM_SLOTS;
    }

    /**
     * @return string[]
     */
    public static function getPickupTimeToSlots()
    {
        return Config::COURIER_PICKUP_TIME_TO_SLOTS;
    }

    /**
     * @param string $time
     *
     * @return bool
     */
    public static function isValidPickupTimeFromSlot($time)
    {
        return in_array($time, Config::COURIER_PICKUP_TIME_FROM_SLOTS, true);
    }

    /**
     * @param string $time
     *
     * @return bool
     */
    public static function isValidPickupTimeToSlot($time)
    {
        return in_array($time, Config::COURIER_PICKUP_TIME_TO_SLOTS, true);
    }

    /**
     * @param string $time
     *
     * @return string|null
     */
    public static function findNextPickupTimeFromSlot($time)
    {
        foreach (Config::COURIER_PICKUP_TIME_FROM_SLOTS as $slot) {
            if ($slot >= $time) {
                return $slot;
            }
        }

        return null;
    }
}
