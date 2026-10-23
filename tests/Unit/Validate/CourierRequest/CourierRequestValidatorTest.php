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
    /**
     * @dataProvider validWeightProvider
     */
    public function testValidWeightIsAccepted($weight)
    {
        $data = new CourierRequestData();
        $data->setWeight($weight);

        $this->assertTrue($this->validator->validateWeight($data));
    }

    public function validWeightProvider()
    {
        return [['1'], ['0.5'], ['.5'], ['12.75'], ['007.5'], [2], [2.5]];
    }

    /**
     * @dataProvider invalidWeightProvider
     */
    public function testInvalidWeightIsRejected($weight)
    {
        $data = new CourierRequestData();
        $data->setWeight($weight);

        $this->assertFalse($this->validator->validateWeight($data));
    }

    public function invalidWeightProvider()
    {
        return [[null], [''], ['0'], ['0.0'], ['-1'], ['0,5'], ['abc'], ['  '], [' 3 '], ["3\n"], ['1e3'], ['5.'], ['INF'], ['NAN'], ['0x1A'], [str_repeat('9', 400)]];
    }

    /**
     * @dataProvider validParcelsCountProvider
     */
    public function testValidParcelsCountIsAccepted($parcelsCount)
    {
        $data = new CourierRequestData();
        $data->setParcelsCount($parcelsCount);

        $this->assertTrue($this->validator->validateParcelsCount($data));
    }

    public function validParcelsCountProvider()
    {
        return [['1'], ['25'], ['999999999'], [3]];
    }

    /**
     * @dataProvider invalidParcelsCountProvider
     */
    public function testInvalidParcelsCountIsRejected($parcelsCount)
    {
        $data = new CourierRequestData();
        $data->setParcelsCount($parcelsCount);

        $this->assertFalse($this->validator->validateParcelsCount($data));
    }

    public function invalidParcelsCountProvider()
    {
        return [[null], [''], ['0'], ['00'], ['01'], ['-1'], ['+1'], ['1.5'], ['abc'], [' 2 '], ["2\n"], ['1000000000'], ['99999999999999999999']];
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
