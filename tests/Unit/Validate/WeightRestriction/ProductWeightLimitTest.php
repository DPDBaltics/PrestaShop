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

use Invertus\dpdBaltics\Validate\Weight\CartWeightValidator;

class ProductWeightLimitTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider disabledLimitProvider
     */
    public function testDisabledLimitAllowsAnyProduct($maxProductWeight)
    {
        $validator = new CartWeightValidator();

        $this->assertTrue($validator->isEachProductWithinWeightLimit($this->getCartMock([40.0]), $maxProductWeight));
    }

    public function disabledLimitProvider()
    {
        return [
            'not configured' => [false],
            'empty' => [''],
            'zero' => ['0'],
        ];
    }

    public function testProductHeavierThanLimitIsRejected()
    {
        $validator = new CartWeightValidator();

        $this->assertFalse($validator->isEachProductWithinWeightLimit($this->getCartMock([1.0, 40.0]), '31.5'));
    }

    public function testProductEqualToLimitIsAllowed()
    {
        $validator = new CartWeightValidator();

        $this->assertTrue($validator->isEachProductWithinWeightLimit($this->getCartMock([31.5]), '31.5'));
    }

    public function testFractionAboveLimitIsRejected()
    {
        $validator = new CartWeightValidator();

        $this->assertFalse($validator->isEachProductWithinWeightLimit($this->getCartMock([31.6]), '31.5'));
    }

    public function testManyLightProductsAboveLimitInTotalAreAllowed()
    {
        $validator = new CartWeightValidator();

        $this->assertTrue($validator->isEachProductWithinWeightLimit($this->getCartMock([20.0, 20.0], 3), '31.5'));
    }

    public function testEmptyCartIsAllowed()
    {
        $validator = new CartWeightValidator();

        $this->assertTrue($validator->isEachProductWithinWeightLimit($this->getCartMock([]), '31.5'));
    }

    public function testDatabaseStringWeightsAreCompared()
    {
        $validator = new CartWeightValidator();

        $this->assertFalse($validator->isEachProductWithinWeightLimit($this->getCartMock(['40.000000']), '31.5'));
        $this->assertTrue($validator->isEachProductWithinWeightLimit($this->getCartMock(['31.500000']), '31.5'));
    }

    public function testQuantityIsIgnored()
    {
        $validator = new CartWeightValidator();

        $this->assertTrue($validator->isEachProductWithinWeightLimit($this->getCartMock([3.0], 10), '31.5'));
        $this->assertFalse($validator->isEachProductWithinWeightLimit($this->getCartMock([32.0], 1), '31.5'));
    }

    /**
     * @dataProvider singleProductWeightProvider
     */
    public function testSingleProductWeight($productWeight, $maxProductWeight, $expected)
    {
        $validator = new CartWeightValidator();

        $this->assertSame($expected, $validator->isProductWeightWithinLimit($productWeight, $maxProductWeight));
    }

    public function singleProductWeightProvider()
    {
        return [
            'limit off' => ['40.000000', false, true],
            'limit zero' => ['40.000000', '0', true],
            'below limit' => ['20.000000', '31.5', true],
            'at limit' => ['31.500000', '31.5', true],
            'above limit' => ['40.000000', '31.5', false],
        ];
    }

    private function getCartMock(array $weights, $quantity = 1)
    {
        $products = [];
        foreach ($weights as $weight) {
            $products[] = [
                'weight' => $weight,
                'cart_quantity' => $quantity,
                'quantity' => $quantity,
            ];
        }

        $cartMock = $this->getMockBuilder(\Cart::class)
            ->setMethods(['getProducts'])
            ->disableOriginalConstructor()
            ->getMock();
        $cartMock->method('getProducts')->willReturn($products);

        return $cartMock;
    }
}
