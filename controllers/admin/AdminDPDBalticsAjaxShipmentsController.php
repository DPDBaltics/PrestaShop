<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /LICENSE
 *
 *  International Registered Trademark & Property of INVERTUS, UAB
 */

use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\Converter\FormDataConverter;
use Invertus\dpdBaltics\DTO\ShipmentData;
use Invertus\dpdBaltics\Factory\ShipmentDataFactory;
use Invertus\dpdBaltics\Repository\ShipmentRepository;
use Invertus\dpdBaltics\Service\Address\ReceiverAddressService;
use Invertus\dpdBaltics\Service\API\LabelApiService;
use Invertus\dpdBaltics\Service\API\ParcelShopSearchApiService;
use Invertus\dpdBaltics\Service\API\ShipmentApiService;
use Invertus\dpdBaltics\Service\Exception\ExceptionService;
use Invertus\dpdBaltics\Service\Label\LabelPrintingService;
use Invertus\dpdBaltics\Service\PudoService;
use Invertus\dpdBaltics\Service\ShipmentService;
use Invertus\dpdBaltics\Validate\ShipmentData\Exception\InvalidShipmentDataField;
use Invertus\dpdBaltics\Validate\ShipmentData\ShipmentDataValidator;
use Invertus\dpdBalticsApi\Api\DTO\Object\ParcelShop;
use Invertus\dpdBalticsApi\Api\DTO\Response\ParcelShopSearchResponse;
use Invertus\dpdBalticsApi\Api\DTO\Response\ShipmentCreationResponse;
use Invertus\dpdBalticsApi\Exception\DPDBalticsAPIException;

require_once dirname(__DIR__) . '/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsAjaxShipmentsController extends AbstractAdminController
{
    protected $ajaxActions = ['save', 'save_and_print', 'updateAddressBlock', 'getProductPriceByID', 'print'];
    protected $ajaxPudoActions = ['searchPudoServices'];
    const DEFAULT_LABEL_TEMPLATE_ID = 1;

    /**
     * Process AJAX call
     */
    public function postProcess()
    {
        $response = ['status' => false];
        $action = Tools::getValue('action');
        $idOrder = Tools::getValue('id_order');
        $order = new Order($idOrder);
        $cartId = Tools::getValue('id_cart');
        $isAbove177 = Config::isPrestashopVersionAbove177();

        /** @var FormDataConverter $formDataConverter */
        $formDataConverter = $this->module->getModuleContainer('invertus.dpdbaltics.converter.form_data_converter');
        $data = Tools::getValue('data');

        if (!$cartId && $idOrder) {
            $cartId = $this->getCartIdByOrderId($idOrder);
        }

        switch ($action) {
            case 'changeReceiverAddressBlock':
                $receiverAddressData = json_decode(Tools::getValue('dpdReceiverAddress'));
                $this->changeReceiverAddressBlock($receiverAddressData, $idOrder);
                break;
            case 'updateAddressBlock':
                $idAddressDelivery = (int) Tools::getValue('id_address_delivery');
                $this->updateAddressBlock($order, $idAddressDelivery);
                break;
            case 'print':
                //Prevent printing in old controller, set printing from Symfony service, as it does not cause corrupted PDF issue
                if ($isAbove177) {
                    $this->returnResponse(['status' => true]);
                }
                $shipmentId = (int) Tools::getValue('shipment_id');
                $labelFormat = Tools::getValue('labelFormat');
                $labelPosition = Tools::getValue('labelPosition');
                $this->returnResponse($this->printLabel($shipmentId, $labelFormat, $labelPosition));
                break;
            case 'print-return':
                $response['status'] = false;
                $shipmentId = (int) Tools::getValue('shipment_id');
                $this->printReturnLabel($shipmentId);
                die();
            case 'save':
            case 'save_and_print':
                $shipmentData = $formDataConverter->convertShipmentFormDataToShipmentObj($data);
                //Prevent printing in old controller, set printing from Symfony service, as it does not cause corrupted PDF issue
                $isPrint = (bool) $action == 'save_and_print' && !$isAbove177;
                $this->returnResponse($this->saveShipment($order, $shipmentData, $isPrint));
                break;
            case 'searchPudoServices':
                $cityName = Tools::getValue('city_name');
                $productId = Tools::getValue('id_product');

                if ($productId) {
                    $product = new DPDProduct($productId);
                    $carrier = Carrier::getCarrierByReference($product->id_reference);
                } else {
                    $carrierId = (int) Tools::getValue('carrier_id');
                    $carrier = new Carrier($carrierId);
                }
                /** @var PudoService $pudoService */
                $pudoService = $this->module->getModuleContainer('invertus.dpdbaltics.service.pudo_service');
                try {
                    $this->returnResponse(
                        $pudoService->searchPudoServices(
                            $cityName,
                            $carrier->id_reference,
                            $cartId
                        )
                    );
                } catch (Exception $e) {
                    $this->returnResponse(
                        [
                            'message' => $this->module->l('Parcel shop search failed!'),
                            'status' => false,
                        ]
                    );
                }
                break;
            case 'updatePudoInfo':
                $pudoId = Tools::getValue('pudo_id');
                $this->returnResponse($this->updatePudoInfo($pudoId));
                break;
            case 'checkIfPudo':
                $productId = Tools::getValue('product_id');
                $dpdProduct = new DPDProduct($productId);
                $this->returnResponse(
                    [
                        'status' => true,
                        'isPudo' => (bool) $dpdProduct->is_pudo
                    ]
                );
                break;
            case 'checkIfHasDeliveryTime':
                $productId = Tools::getValue('product_id');
                $dpdProduct = new DPDProduct($productId);
                $hasDeliveryTime = Config::productHasDeliveryTime($dpdProduct->product_reference);
                $this->returnResponse(
                    [
                        'status' => true,
                        'hasDelivery' => $hasDeliveryTime
                    ]
                );
                break;
            case 'printLabelFromList':
                $orderId = Tools::getValue('id_order');
                $this->returnResponse($this->printLabelFromList($orderId));
                break;
            case 'printMultipleLabelsFromList':
                $orders = json_decode(Tools::getValue('order_ids'));
                $this->returnResponse($this->printMultipleLabelsFromList($orders));
                break;
            case  'getCarrierPhoneTemplate':
                $idCart = Tools::getValue('id_cart');
                $idCarrier = Tools::getValue('id_carrier');

                $carrier = new Carrier($idCarrier);

                if ($carrier->external_module_name !== $this->module->name) {
                    $response = json_encode(
                        [
                            'carrierPhoneTemplate' => ''
                        ]
                    );
                    $this->ajaxRender($response);
                    exit;
                }
                /** @var \Invertus\dpdBaltics\Service\CarrierPhoneService $carrierPhoneService */
                $carrierPhoneService = $this->module->getModuleContainer('invertus.dpdbaltics.service.carrier_phone_service');

                $response = [
                    'carrierPhoneTemplate' => $carrierPhoneService->getCarrierPhoneTemplate($idCart, $carrier->id_reference)
                ];
                $response = json_encode($response);
                $this->ajaxRender($response);
                exit;
                break;
            default:
                break;
        }

        $response['message'] = $this->module->l('Unexpected error occurred.');
        $this->returnResponse($response);
    }

    private function printLabelFromList($orderId)
    {
        /** @var ShipmentService $shipmentService */
        $shipmentService = $this->module->getModuleContainer('invertus.dpdbaltics.service.shipment_service');

        return $shipmentService->formatLabelShipmentPrintResponse($orderId);
    }

    public function printMultipleLabelsFromList($orderIds)
    {
        /** @var ShipmentService $shipmentService */
        $shipmentService = $this->module->getModuleContainer('invertus.dpdbaltics.service.shipment_service');

        return $shipmentService->formatMultipleLabelShipmentPrintResponse($orderIds);

    }


    /**
     * @param Order $order
     * @param ShipmentData $shipmentData
     * @param false $print
     * @return mixed
     */
    private function saveShipment(Order $order, ShipmentData $shipmentData, $print = false)
    {
        $shipmentService = $this->module->getModuleContainer('invertus.dpdbaltics.service.shipment_service');

        return $shipmentService->saveShipment($order, $shipmentData, $print);
    }

    /**
     * @param array $response
     * @throws PrestaShopException
     */
    protected function returnResponse(array $response)
    {
        $response = json_encode($response);

        $this->ajaxRender($response);
        exit;
    }

    private function changeReceiverAddressBlock($receiverAddressData, $orderId)
    {
        if (preg_match('#[^0-9]#', $receiverAddressData->phone)) {
            $response['message'] = $this->module->l('Invalid phone number.');
            $this->returnResponse($response);
        }
        $order = new Order($orderId);
        /** @var ReceiverAddressService $receiverAddressService */
        $receiverAddressService = $this->module->getModuleContainer('invertus.dpdbaltics.service.address.receiver_address_service');
        $receiverCustomAddress = $receiverAddressService->addReceiverCustomAddress($receiverAddressData, $orderId);

        if (false === $receiverCustomAddress) {
            $response['message'] = $this->module->l('Failed to add new recipient address.');
            $this->returnResponse($response);
        }

        if (!$receiverAddressService->deletePreviousEditedAddress($order->id)) {
            $response['message'] = $this->module->l('Failed to delete previously edited address');
            $this->returnResponse($response);
        }

        $receiverAddress = new DPDReceiverAddress();
        $receiverAddress->id_order = $orderId;
        $receiverAddress->id_origin_address = $receiverCustomAddress->id;
        try {
            $receiverAddress->save();
            $addressResponse = $receiverAddressService->processUpdateAddressBlock($order, $receiverCustomAddress->id);
        } catch (Exception $e) {
            $response['message'] = $this->module->l('Failed to update address for this order');
            $this->returnResponse($response);
        }

        if (false === $addressResponse) {
            $response['message'] = $this->module->l('Failed to update address for this order');
            $this->returnResponse($response);
        }

        $addressResponse['message'] = $this->module->l('Receiver address successfully updated for this order.');
        $this->returnResponse($addressResponse);
    }

    private function updateAddressBlock($order, $idAddressDelivery)
    {
        /** @var ReceiverAddressService $receiverAddressService */
        $receiverAddressService = $this->module->getModuleContainer('invertus.dpdbaltics.service.address.receiver_address_service');
        try {
            $addressResponse = $receiverAddressService->processUpdateAddressBlock($order, $idAddressDelivery);
        } catch (Exception $e) {
            $response['message'] = $this->module->l('Failed to update address.');
            $this->returnResponse($response);
        }

        if (false === $addressResponse) {
            $response['message'] = $this->module->l('Failed to update address.');
            $this->returnResponse($response);
        }

        $this->returnResponse($addressResponse);
    }

    private function printLabel($shipmentId, $labelFormat, $labelPosition)
    {
        /** @var LabelPrintingService $labelPrintingService */
        $labelPrintingService = $this->module->getModuleContainer('invertus.dpdbaltics.service.label.label_printing_service');

        return $labelPrintingService->setLabelOptions($shipmentId, $labelFormat, $labelPosition);
    }

    private function  printReturnLabel($shipmentId)
    {
        /** @var LabelApiService $labelApiService */
        $labelApiService = $this->module->getModuleContainer('invertus.dpdbaltics.service.api.label_api_service');
        $returnTemplateId = Tools::getValue('return_template_id') ?: self::DEFAULT_LABEL_TEMPLATE_ID;

        $dpdShipment = new DPDShipment($shipmentId);
        if ($dpdShipment->return_pl_number) {
            try {
                $labelApiService->printLabel($dpdShipment->return_pl_number, false, false, true);
                exit();
            } catch (Exception $e) {
                $response['status'] = false;
                $this->returnResponse($response);
            }
        }
        try {
            /** @var ShipmentService $shipmentService */
            $shipmentService = $this->module->getModuleContainer('invertus.dpdbaltics.service.shipment_service');
            $dpdShipment = $shipmentService->createReturnServiceShipment($returnTemplateId, $dpdShipment->id_order);
            $labelApiService->printLabel($dpdShipment->return_pl_number, false, false, true);
            exit();
        } catch (DPDBalticsAPIException $e) {
            /** @var ExceptionService $exceptionService */
            $exceptionService = $this->module->getModuleContainer('invertus.dpdbaltics.service.exception.exception_service');
            $errorMessage = $exceptionService->getErrorMessageForException(
                $e,
                $exceptionService->getAPIErrorMessages()
            );
            $this->context->cookie->dpd_error = json_encode($errorMessage);
            //todo: it should display error somehow but now it just shows error in empty page.
            exit($errorMessage);
        }
    }

    private function setLabelOptions(ShipmentData $shipmentData, $shipmentId, $orderId)
    {
        $labelPrintingService = $this->module->getModuleContainer('invertus.dpdbaltics.service.label.label_printing_service');

        return $labelPrintingService->printAndSaveLabel($shipmentData, $shipmentId, $orderId);
    }

    private function updatePudoInfo($pudoId)
    {
        /** @var \Invertus\dpdBaltics\Service\Parcel\ParcelShopService $parcelShopService */
        $parcelShopService = $this->module->getModuleContainer('invertus.dpdbaltics.service.parcel.parcel_shop_service');
        try {
            /** @var ParcelShop[] $parcelShops */
            $parcelShops = $parcelShopService->getParcelShopByShopId($pudoId);
        } catch (Exception $e) {
            $response['status'] = false;
            $response['message'] = $this->module->l('Failed to find parcel shops: ') . $e->getMessage();
            $this->returnResponse($response);
        }

        /** @var PudoService $pudoService */
        $pudoService = $this->module->getModuleContainer('invertus.dpdbaltics.service.pudo_service');
        $pudoServices = $pudoService->setPudoServiceTypes($parcelShops);
        $pudoServices = $pudoService->formatPudoServicesWorkHours($pudoServices);

        $this->context->smarty->assign(
            [
                'receiverAddressCountries' => Country::getCountries($this->context->language->id, true),
                'selectedPudo' => $pudoServices[0]

            ]
        );

        return [
            'template' => $this->context->smarty->fetch(
                $this->module->getLocalPath() . '/views/templates/hook/admin/partials/pudo-info.tpl'
            ),
            'parcel_name' => $pudoServices[0]->getCompany(),
            'status' => true,
        ];
    }

    /**
     * @param $idOrder
     *
     * @return int
     */
    private function getCartIdByOrderId($idOrder)
    {
        return (int) Order::getCartIdStatic($idOrder);
    }
}
