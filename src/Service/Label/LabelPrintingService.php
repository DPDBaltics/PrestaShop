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


namespace Invertus\dpdBaltics\Service\Label;

use DPDBaltics;
use DPDShipment;
use http\Exception;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\DTO\ShipmentData;
use Invertus\dpdBaltics\Logger\Logger;
use Invertus\dpdBaltics\Service\API\ShipmentApiService;
use Invertus\dpdBaltics\Service\Exception\ExceptionService;
use Invertus\dpdBalticsApi\Api\DTO\Response\ShipmentCreationResponse;
use Invertus\dpdBalticsApi\Exception\DPDBalticsAPIException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class LabelPrintingService
{
    /**
     * @var DPDBaltics
     */
    private $module;
    /**
     * @var ShipmentApiService
     */
    private $shipmentApiService;
    /**
     * @var ExceptionService
     */
    private $exceptionService;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        DPDBaltics $module,
        ShipmentApiService $shipmentApiService,
        ExceptionService $exceptionService,
        Logger $logger
    )
    {
        $this->module = $module;
        $this->shipmentApiService = $shipmentApiService;
        $this->exceptionService = $exceptionService;
        $this->logger = $logger;
    }

    public function setLabelOptions($shipmentId, $labelFormat, $labelPosition)
    {
        $shipment = new DPDShipment($shipmentId);
        $response['status'] = false;
        if ($shipment->printed_label && !$shipment->printed_manifest) {
            $shipment->label_format = $labelFormat;
            $shipment->label_position = $labelPosition;
            try {
                $shipment->update();
            } catch (Exception $e) {
                $response['message'] = $this->module->l(
                    sprintf('Failed to update shipment label Error: %s', $e->getMessage(). ' ID cart: '. $shipmentId)
                );
                $this->logger->error($response['message']);

                return $response;
            }
            $response['status'] = true;
        }
        $response['id_dpd_shipment'] = $shipmentId;

        return $response;
    }

    public function printAndSaveLabel(ShipmentData $shipmentData, $shipmentId, $orderId)
    {
        $response['status'] = false;

        try {
            //Creates shipment in DPP platform
            /** @var ShipmentCreationResponse $shipmentCreationResponse */
            $shipmentCreationResponse = $this->shipmentApiService->createShipment(
                $shipmentData->getAddressId(),
                $shipmentData,
                $orderId
            );

        } catch (DPDBalticsAPIException $e) {
            $response['message'] = $this->exceptionService->getErrorMessageForException(
                $e,
                $this->exceptionService->getAPIErrorMessages()
            ). ' ID order: '. $orderId;
            $this->logger->error($response['message']);

            return $response;

        } catch (Exception $e) {
            $response['message'] = sprintf(
                $this->module->l('Failed to create DPD shipment: %s (order ID: %s)'),
                $e->getMessage(),
                $orderId
            );
            $this->logger->error($response['message']);

            return $response;
        }

        if ($shipmentCreationResponse->getStatus() !== Config::API_SUCCESS_STATUS) {
            $apiError = $this->exceptionService->getApiErrorMessage($shipmentCreationResponse->getErrLog());
            $response['message'] = sprintf(
                $this->module->l('Failed to create DPD shipment: %s (order ID: %s)'),
                $apiError,
                $orderId
            );
            $this->logger->error($response['message']);

            return $response;
        }

        //Sets pudo terminal from response
        $shipment = new DPDShipment($shipmentId);
        $shipment->pl_number = $shipmentCreationResponse->getPlNumbersAsString();
        try {
            $shipment->update();
        } catch (Exception $e) {
            $exception = $e->getMessage(). ' ID order: '. $orderId;
            $response['message'] = $this->module->l(
                "Failed to update shipment: {$exception}"
            );
            $this->logger->error($response['message']);

            return $response;
        }

        $response['status'] = true;
        $response['id_dpd_shipment'] = $shipmentId;

        //Returns response and possibly fetches print function in main module class
        return $response;
    }
}
