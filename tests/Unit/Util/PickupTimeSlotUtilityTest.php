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

use Invertus\dpdBaltics\Util\PickupTimeSlotUtility;
use PHPUnit\Framework\TestCase;

class PickupTimeSlotUtilityTest extends TestCase
{
    public function testValidPickupTimeFromSlots()
    {
        $this->assertTrue(PickupTimeSlotUtility::isValidPickupTimeFromSlot('08:00'));
        $this->assertTrue(PickupTimeSlotUtility::isValidPickupTimeFromSlot('12:30'));
        $this->assertTrue(PickupTimeSlotUtility::isValidPickupTimeFromSlot('15:00'));

        $this->assertFalse(PickupTimeSlotUtility::isValidPickupTimeFromSlot('08:15'));
        $this->assertFalse(PickupTimeSlotUtility::isValidPickupTimeFromSlot('16:00'));
        $this->assertFalse(PickupTimeSlotUtility::isValidPickupTimeFromSlot('11:35:26'));
        $this->assertFalse(PickupTimeSlotUtility::isValidPickupTimeFromSlot(''));
    }

    public function testValidPickupTimeToSlots()
    {
        $this->assertTrue(PickupTimeSlotUtility::isValidPickupTimeToSlot('15:00'));
        $this->assertTrue(PickupTimeSlotUtility::isValidPickupTimeToSlot('17:00'));
        $this->assertTrue(PickupTimeSlotUtility::isValidPickupTimeToSlot('18:00'));

        $this->assertFalse(PickupTimeSlotUtility::isValidPickupTimeToSlot('08:00'));
        $this->assertFalse(PickupTimeSlotUtility::isValidPickupTimeToSlot('17:30'));
        $this->assertFalse(PickupTimeSlotUtility::isValidPickupTimeToSlot(''));
    }

    public function testFindNextPickupTimeFromSlotSnapsForward()
    {
        $this->assertSame('08:00', PickupTimeSlotUtility::findNextPickupTimeFromSlot('06:45'));
        $this->assertSame('08:00', PickupTimeSlotUtility::findNextPickupTimeFromSlot('08:00'));
        $this->assertSame('12:00', PickupTimeSlotUtility::findNextPickupTimeFromSlot('11:35'));
        $this->assertSame('12:30', PickupTimeSlotUtility::findNextPickupTimeFromSlot('12:05'));
        $this->assertSame('15:00', PickupTimeSlotUtility::findNextPickupTimeFromSlot('14:31'));
    }

    public function testFindNextPickupTimeFromSlotReturnsNullAfterLastSlot()
    {
        $this->assertNull(PickupTimeSlotUtility::findNextPickupTimeFromSlot('15:01'));
        $this->assertNull(PickupTimeSlotUtility::findNextPickupTimeFromSlot('23:59'));
    }
}
