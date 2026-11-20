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
    define('_PS_VERSION_', '1.7.4.0');
}

use Invertus\dpdBaltics\Validate\ReturnLabel\ReturnLabelAccessValidator;

class ReturnLabelAccessValidatorTest extends PHPUnit_Framework_TestCase
{
    const OWNER_ID = 6;

    public function testOwnerOwnsOrder()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertTrue($validator->isOwner(self::OWNER_ID, self::OWNER_ID));
        $this->assertTrue($validator->isOwner('6', 6));
    }

    public function testAnotherCustomerDoesNotOwnOrder()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertFalse($validator->isOwner(2, self::OWNER_ID));
    }

    public function testVisitorDoesNotOwnOrder()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertFalse($validator->isOwner(0, self::OWNER_ID));
        $this->assertFalse($validator->isOwner(null, null));
    }

    public function testNobodyOwnsMissingOrder()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertFalse($validator->isOwner(0, 0));
        $this->assertFalse($validator->isOwner(self::OWNER_ID, 0));
    }

    public function testReturnIsAvailableForDeliveredDpdOrder()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertTrue($validator->isReturnAvailable(true, false, true, true));
        $this->assertTrue($validator->isReturnAvailable('1', false, true, '3'));
    }

    public function testReturnIsNotAvailableWhenReturnServiceIsDisabled()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertFalse($validator->isReturnAvailable(false, false, true, true));
        $this->assertFalse($validator->isReturnAvailable('0', false, true, true));
    }

    public function testReturnIsNotAvailableInEstonia()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertFalse($validator->isReturnAvailable(true, true, true, true));
    }

    public function testReturnIsNotAvailableBeforeDelivery()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertFalse($validator->isReturnAvailable(true, false, false, true));
    }

    public function testReturnIsNotAvailableWithoutDpdShipment()
    {
        $validator = new ReturnLabelAccessValidator();

        $this->assertFalse($validator->isReturnAvailable(true, false, true, false));
    }
}
