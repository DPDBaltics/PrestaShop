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

use Invertus\dpdBaltics\DTO\CourierRequestData;
use Invertus\dpdBaltics\Validate\CourierRequest\CourierRequestValidator;
use PHPUnit\Framework\TestCase;

class CourierRequestValidatorTest extends TestCase
{
    /**
     * @var CourierRequestValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new CourierRequestValidator();
    }

    public function testValidSlotPairPassesSlotValidation()
    {
        $data = $this->createCourierRequestData('08:00', '17:00');

        $this->assertTrue($this->validator->validatePickupTimeSlots($data));
    }

    public function testNonWhitelistedFromSlotFailsSlotValidation()
    {
        $data = $this->createCourierRequestData('11:35', '17:00');

        $this->assertFalse($this->validator->validatePickupTimeSlots($data));
    }

    public function testNonWhitelistedToSlotFailsSlotValidation()
    {
        $data = $this->createCourierRequestData('08:00', '12:00');

        $this->assertFalse($this->validator->validatePickupTimeSlots($data));
    }

    public function testFromSlotEqualOrLaterThanToSlotFailsSlotValidation()
    {
        $data = $this->createCourierRequestData('15:00', '15:00');

        $this->assertFalse($this->validator->validatePickupTimeSlots($data));
    }

    public function testMinimalIntervalValidationStillPassesForComposedDatetimes()
    {
        $data = $this->createCourierRequestData('08:00', '17:00');
        $data->setPickupTime('2030-02-20 08:00:00');
        $data->setSenderWorkUntil('2030-02-20 17:00:00');

        $this->assertTrue($this->validator->validate($data, 'LT'));
    }

    public function testMinimalIntervalValidationStillFailsWhenIntervalTooShort()
    {
        $data = $this->createCourierRequestData('15:00', '16:00');
        $data->setPickupTime('2030-02-20 15:00:00');
        $data->setSenderWorkUntil('2030-02-20 16:00:00');

        $this->assertFalse($this->validator->validate($data, 'LT'));
    }

    private function createCourierRequestData($from, $to)
    {
        $data = new CourierRequestData();
        $data->setPickupDate('2030-02-20');
        $data->setPickupTimeFrom($from);
        $data->setPickupTimeTo($to);

        return $data;
    }
}
