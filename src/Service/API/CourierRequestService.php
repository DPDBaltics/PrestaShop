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


namespace Invertus\dpdBaltics\Service\API;

use Country;
use DPDBaltics;
use Invertus\dpdBaltics\DTO\courierRequestData;
use Invertus\dpdBaltics\Service\API\Parser\CourierRequestResponseParser;
use Invertus\dpdBalticsApi\Api\DTO\Request\CourierRequestRequest;
use Invertus\dpdBalticsApi\Api\DTO\Response\courierRequestResponse;
use Invertus\dpdBalticsApi\Factory\APIRequest\courierRequestFactory;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CourierRequestService
{

    /**
     * @var CourierRequestFactory
     */
    private $courierRequestFactory;
    /**
     * @var DPDBaltics
     */
    private $module;
    /**
     * @var CourierRequestResponseParser
     */
    private $responseParser;

    public function __construct(
        CourierRequestFactory $courierRequestFactory,
        DPDBaltics $module,
        CourierRequestResponseParser $responseParser
    ) {
        $this->courierRequestFactory = $courierRequestFactory;
        $this->module = $module;
        $this->responseParser = $responseParser;
    }

    public function createCourierRequest(CourierRequestData $courierRequestData)
    {
        $senderIsoCode = Country::getIsoById($courierRequestData->getSenderIdWsCountry());

        $postalCode = preg_replace(
            '/[^0-9]/',
            '',
            $courierRequestData->getSenderPostalCode()
        );
        
        $request = new CourierRequestRequest(
            $courierRequestData->getOrderNr(),
            $courierRequestData->getSenderAddress(),
            $courierRequestData->getSenderCity(),
            $senderIsoCode,
            $postalCode,
            $courierRequestData->getSenderName(),
            $courierRequestData->getSenderPhoneCode() . $courierRequestData->getSenderPhone(),
            $courierRequestData->getSenderWorkUntil(),
            $courierRequestData->getPickupTime(),
            $courierRequestData->getWeight(),
            $courierRequestData->getParcelsCount()
        );

        if ($courierRequestData->getPickupTimeFrom() && $courierRequestData->getPickupTimeTo()) {
            $request->setPickupDate($courierRequestData->getPickupDate());
            $request->setPickupTimeFrom($courierRequestData->getPickupTimeFrom());
            $request->setPickupTimeTo($courierRequestData->getPickupTimeTo());
        }

        $courierRequest = $this->courierRequestFactory->makecourierRequest();

        /** @var courierRequestResponse $response */
        $response = $courierRequest->courierRequest($request);

        if (!$this->responseParser->isSuccess($response)) {
            return [
                'status' => false,
                'message' => $this->responseParser->getError($response)
            ];
        }

        return [
            'status' => true,
            'message' => $this->module->l('courier request was successfully created!')
        ];
    }
}
