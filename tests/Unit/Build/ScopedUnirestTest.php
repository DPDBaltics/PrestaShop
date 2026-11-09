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


use Invertus\dpdBalticsApi\Api\ApiRequest;
use Invertus\dpdBalticsApi\Factory\APIRequest\ApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ScopedUnirestTest extends TestCase
{
    const CLOSED_PORT_URL = 'http://127.0.0.1:1/';

    /**
     * Another module's Unirest\Request is loaded first, as PrestaShop does when its directory sorts before dpdbaltics.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testApiRequestKeepsWorkingWhenAnotherModuleLoadsUnirestFirst()
    {
        if (!class_exists('Invertus\dpdBaltics\Vendor\Unirest\Request')) {
            $this->markTestSkipped('Vendor is not scoped, run scripts/scope-vendor.sh --dev first');
        }

        require_once __DIR__ . '/Fixture/ForeignUnirestRequest.php';

        $apiRequest = new ApiRequest(new ApiClient(self::CLOSED_PORT_URL, 'user', 'pass'), new NullLogger(), '1.0.0', '9.1.3');

        $thrown = null;
        try {
            $apiRequest->post('ws-mapper-rest/parcelShopSearch_', ['query' => []]);
        } catch (\Exception $exception) {
            $thrown = $exception;
        } catch (\Error $error) {
            $thrown = $error;
        }

        $this->assertNotNull($thrown, 'A request to a closed port must fail');
        $this->assertSame('Invertus\dpdBaltics\Vendor\Unirest\Exception', get_class($thrown), $thrown->getMessage());
    }
}
