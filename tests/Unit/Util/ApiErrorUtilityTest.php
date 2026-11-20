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


use Invertus\dpdBaltics\Util\ApiErrorUtility;
use PHPUnit\Framework\TestCase;

class ApiErrorUtilityTest extends TestCase
{
    /**
     * @dataProvider authenticationErrorProvider
     */
    public function testIsAuthenticationError($rawErrorLog, $expected)
    {
        $this->assertSame($expected, ApiErrorUtility::isAuthenticationError($rawErrorLog));
    }

    public function authenticationErrorProvider()
    {
        return [
            'exact DPD message' => ['Unable to find user to authenticate!', true],
            'different casing' => ['unable to FIND user to authenticate', true],
            'authentication failed' => ['Authentication failed', true],
            'invalid credentials' => ['Invalid username or password', true],
            'unrelated error' => ['Parcel weight is required', false],
            'empty string' => ['', false],
            'null' => [null, false],
        ];
    }
}
