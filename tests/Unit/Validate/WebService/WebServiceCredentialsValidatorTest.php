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

use Invertus\dpdBaltics\Service\API\WebServiceAuthApiService;
use Invertus\dpdBaltics\Validate\WebService\WebServiceCredentialsValidator;
use Unirest\Response;

class WebServiceCredentialsValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider responseProvider
     */
    public function testValidateMapsDpdResponse($code, $rawBody, $expected)
    {
        $authApiService = $this->createMock(WebServiceAuthApiService::class);
        $authApiService->method('checkCredentials')->willReturn(new Response($code, $rawBody, []));

        $validator = new WebServiceCredentialsValidator($authApiService);

        $this->assertSame($expected, $validator->validate('user', 'pass', 'LT', true));
    }

    public function responseProvider()
    {
        return [
            'authenticated' => [200, '{"status":"ok","errlog":"","parcelshops":null}', WebServiceCredentialsValidator::VALID],
            'wrong password' => [
                200,
                '{"status":"err","errlog":"Failed to Authenticate User. Check your user name and password! "}',
                WebServiceCredentialsValidator::WRONG_PASSWORD,
            ],
            'user not on this country endpoint' => [
                200,
                '{"status":"err","errlog":"Unable to find user to authenticate!"}',
                WebServiceCredentialsValidator::UNKNOWN_USER,
            ],
            'live gateway rejects credentials' => [401, '<!DOCTYPE html><title>Unauthorized</title>', WebServiceCredentialsValidator::REJECTED],
            'other dpd error' => [200, '{"status":"err","errlog":"Wrong input parameters!"}', WebServiceCredentialsValidator::UNVERIFIED],
            'server error' => [502, '<html>Bad Gateway</html>', WebServiceCredentialsValidator::UNVERIFIED],
            'not json' => [200, 'maintenance', WebServiceCredentialsValidator::UNVERIFIED],
        ];
    }

    public function testValidateReturnsUnverifiedWhenDpdIsUnreachable()
    {
        $authApiService = $this->createMock(WebServiceAuthApiService::class);
        $authApiService->method('checkCredentials')->willThrowException(new \Unirest\Exception('timeout'));

        $validator = new WebServiceCredentialsValidator($authApiService);

        $this->assertSame(WebServiceCredentialsValidator::UNVERIFIED, $validator->validate('user', 'pass', 'LT', true));
    }

    public function testValidateReturnsUnverifiedWhenHttpClientFatals()
    {
        $authApiService = $this->createMock(WebServiceAuthApiService::class);
        $authApiService->method('checkCredentials')->willReturnCallback(function () {
            throw new \Error('Non-static method Unirest\Request::timeout() cannot be called statically');
        });

        $validator = new WebServiceCredentialsValidator($authApiService);

        $this->assertSame(WebServiceCredentialsValidator::UNVERIFIED, $validator->validate('user', 'pass', 'LT', true));
    }

    /**
     * @dataProvider urlProvider
     */
    public function testValidateCallsEndpointOfSelectedCountryAndMode($countryIso, $testMode, $expectedUrl)
    {
        $authApiService = $this->createMock(WebServiceAuthApiService::class);
        $authApiService->expects($this->once())
            ->method('checkCredentials')
            ->with($expectedUrl, 'user', 'pass', $countryIso)
            ->willReturn(new Response(200, '{"status":"ok","errlog":""}', []));

        $validator = new WebServiceCredentialsValidator($authApiService);
        $validator->validate('user', 'pass', $countryIso, $testMode);
    }

    public function urlProvider()
    {
        return [
            ['LT', true, 'https://lt.integration.dpd.eo.pl'],
            ['LV', true, 'https://lv.integration.dpd.eo.pl'],
            ['EE', false, 'https://integration.dpd.ee'],
            ['LT', false, 'https://integracijos.dpd.lt'],
        ];
    }
}
