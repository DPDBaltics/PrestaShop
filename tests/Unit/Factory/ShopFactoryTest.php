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

use Invertus\dpdBaltics\Factory\ShopFactory;
use Invertus\dpdBaltics\Repository\ParcelShopRepository;
use Invertus\dpdBalticsApi\Api\DTO\Object\OpeningHours;
use PHPUnit\Framework\TestCase;

class ShopFactoryTest extends TestCase
{
    public function testLoadsWorkHoursForAllShopsWithOneQuery()
    {
        $repository = $this->getRepositoryMock();
        $repository->expects($this->never())->method('getShopWorkHoursByShopId');
        $repository->expects($this->once())
            ->method('getShopWorkHoursByShopIds')
            ->with(['LT90001', 'LT90002', 'LT90003'])
            ->willReturn([
                $this->workHourRow('LT90001', 'monday', '08:00'),
                $this->workHourRow('LT90002', 'monday', '09:00'),
                $this->workHourRow('LT90001', 'tuesday', '10:00'),
            ]);

        $factory = new ShopFactory($repository);
        $shops = $factory->createShop([
            $this->shopRow('LT90001'),
            $this->shopRow('LT90002'),
            $this->shopRow('LT90003'),
        ]);

        $this->assertCount(3, $shops);
        $this->assertSame(['LT90001', 'LT90002', 'LT90003'], array_map(function ($shop) {
            return $shop->getParcelShopId();
        }, $shops));
        $this->assertSame([['monday', '08:00'], ['tuesday', '10:00']], $this->summarize($shops[0]->getOpeningHours()));
        $this->assertSame([['monday', '09:00']], $this->summarize($shops[1]->getOpeningHours()));
        $this->assertSame([], $shops[2]->getOpeningHours());
        $this->assertInstanceOf(OpeningHours::class, $shops[0]->getOpeningHours()[0]);
    }

    public function testGivesSameHoursToRepeatedAndNumericShopIds()
    {
        $repository = $this->getRepositoryMock();
        $repository->expects($this->once())
            ->method('getShopWorkHoursByShopIds')
            ->with(['12345', '12345', 'LT90001'])
            ->willReturn([
                $this->workHourRow('12345', 'monday', '07:00'),
            ]);

        $factory = new ShopFactory($repository);
        $shops = $factory->createShop([
            $this->shopRow('12345'),
            $this->shopRow('12345'),
            $this->shopRow('LT90001'),
        ]);

        $this->assertSame([['monday', '07:00']], $this->summarize($shops[0]->getOpeningHours()));
        $this->assertSame([['monday', '07:00']], $this->summarize($shops[1]->getOpeningHours()));
        $this->assertSame([], $shops[2]->getOpeningHours());
    }

    public function testDoesNotQueryWorkHoursWhenThereAreNoShops()
    {
        $repository = $this->getRepositoryMock();
        $repository->expects($this->never())->method('getShopWorkHoursByShopId');
        $repository->expects($this->never())->method('getShopWorkHoursByShopIds');

        $factory = new ShopFactory($repository);

        $this->assertSame([], $factory->createShop([]));
    }

    private function getRepositoryMock()
    {
        return $this->getMockBuilder(ParcelShopRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShopWorkHoursByShopId', 'getShopWorkHoursByShopIds'])
            ->getMock();
    }

    private function summarize(array $openingHours)
    {
        return array_map(function (OpeningHours $openingHour) {
            return [$openingHour->getWeekDay(), $openingHour->getOpenMorning()];
        }, $openingHours);
    }

    private function shopRow($parcelShopId)
    {
        return [
            'parcel_shop_id' => $parcelShopId,
            'company' => 'Test pickup point',
            'country' => 'LT',
            'city' => 'VILNIUS',
            'p_code' => '01100',
            'street' => 'Test g. 1',
            'email' => 'pickup@example.test',
            'longitude' => '25.2797',
            'latitude' => '54.6872',
        ];
    }

    private function workHourRow($parcelShopId, $weekDay, $openMorning)
    {
        return [
            'parcel_shop_id' => $parcelShopId,
            'week_day' => $weekDay,
            'open_morning' => $openMorning,
            'close_morning' => '12:00',
            'close_afternoon' => '18:00',
            'open_afternoon' => '13:00',
        ];
    }
}
