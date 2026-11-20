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


namespace Invertus\dpdBaltics\Service\Exception;

use DPDBaltics;
use Exception;
use Invertus\dpdBaltics\Util\ApiErrorUtility;
use Invertus\dpdBaltics\Validate\ShipmentData\Exception\InvalidShipmentDataField;
use Invertus\dpdBalticsApi\Exception\DPDBalticsAPIException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ExceptionService
{
    const SHORT_CLASS_NAME = 'ExceptionService';

    /**
     * @var DPDBaltics
     */
    private $module;

    public function __construct(DPDBaltics $module)
    {
        $this->module = $module;
    }

    public function getAPIErrorMessages()
    {
        return [
            DPDBalticsAPIException::class => [
                DPDBalticsAPIException::PARCEL_SHOP_SEARCH =>
                $this->module->l('Failed to find parcel shops', self::SHORT_CLASS_NAME),
                DPDBalticsAPIException::SHIPMENT_CREATION =>
                $this->module->l('Failed to create shipment', self::SHORT_CLASS_NAME),
                DPDBalticsAPIException::PARCEL_PRINT =>
                $this->module->l('Failed to print parcel label', self::SHORT_CLASS_NAME),
                DPDBalticsAPIException::CLOSING_MANIFEST =>
                    $this->module->l('Failed to close manifest', self::SHORT_CLASS_NAME),
                DPDBalticsAPIException::COLLECTION_REQUEST =>
                $this->module->l('Failed to create collection request', self::SHORT_CLASS_NAME),
                DPDBalticsAPIException::COURIER_REQUEST =>
                $this->module->l('Failed to create courier request', self::SHORT_CLASS_NAME),
            ]
        ];
    }

    public function getApiErrorMessage($rawErrorLog)
    {
        if (ApiErrorUtility::isAuthenticationError($rawErrorLog)) {
            return $this->module->l(
                'DPD rejected the API credentials. Please check that the API username and password in Basic Settings match the web service credentials provided by DPD, not your DPD web portal login.',
                self::SHORT_CLASS_NAME
            );
        }

        return $rawErrorLog;
    }

    public function getShipmentFieldErrorMessages()
    {
        return [
            InvalidShipmentDataField::class => [
                InvalidShipmentDataField::ERROR_MESSAGE_CODE_INVALID_PRODUCT =>
                $this->module->l('Shipment data field \'Product\' is invalid', self::SHORT_CLASS_NAME)
            ]
        ];
    }

    public function getErrorMessageForException(Exception $exception, array $messages)
    {
        $exceptionType = get_class($exception);
        $exceptionCode = $exception->getCode();
        $exceptionMesage = $exception->getMessage();
        if (isset($messages[$exceptionType])) {
            $message = $messages[$exceptionType];

            if (is_string($message)) {
                return $message. ' - '. $exceptionMesage;
            }

            if (is_array($message) && isset($message[$exceptionCode])) {
                return $message[$exceptionCode]. ' - '. $exceptionMesage;
            }
        }

        return $this->module->l('Unknown exception in DPDBaltics', self::SHORT_CLASS_NAME);
    }
}