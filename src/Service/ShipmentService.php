<?php

namespace Invertus\dpdBaltics\Service;

use Address;
use Carrier;
use Configuration;
use Country;
use Customer;
use DPDAddressTemplate;
use DPDBaltics;
use DPDOrderDeliveryTime;
use DPDParcel;
use DPDShipment;
use Exception;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\DTO\ShipmentData;
use Invertus\dpdBaltics\Factory\ShipmentDataFactory;
use Invertus\dpdBaltics\Helper\ShipmentHelper;
use Invertus\dpdBaltics\Repository\AddressTemplateRepository;
use Invertus\dpdBaltics\Repository\OrderDeliveryTimeRepository;
use Invertus\dpdBaltics\Repository\OrderRepository;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Repository\ShipmentRepository;
use Invertus\dpdBaltics\Service\API\ShipmentApiService;
use Invertus\dpdBaltics\Service\Exception\ExceptionService;
use Invertus\dpdBaltics\Service\Label\LabelPrintingService;
use Invertus\dpdBaltics\Validate\ShipmentData\Exception\InvalidShipmentDataField;
use Invertus\dpdBaltics\Validate\ShipmentData\ShipmentDataValidator;
use Invertus\dpdBalticsApi\Api\DTO\Request\ShipmentCreationRequest;
use Invertus\dpdBalticsApi\Api\DTO\Response\ShipmentCreationResponse;
use Invertus\dpdBalticsApi\Exception\DPDBalticsAPIException;
use Language;
use Order;
use OrderCarrier;

class ShipmentService
{

    /**
     * @var DPDBaltics
     */
    private $module;

    /**
     * @var Language
     */
    private $language;

    /**
     * @var ShipmentRepository
     */
    private $shipmentRepository;

    /**
     * @var ShipmentHelper
     */
    private $shipmentHelper;

    /**
     * @var ShipmentApiService
     */
    private $shipmentApiService;

    /**
     * @var ShipmentDataValidator
     */
    private $shipmentDataValidator;

    /**
     * @var ExceptionService
     */
    private $exceptionService;

    /**
     * @var LabelPrintingService
     */
    private $labelPrintingService;

    /**
     * @var PudoService
     */
    private $pudoService;

    /**
     * @var OrderDeliveryTimeRepository
     */
    private $orderDeliveryTimeRepository;

    /**
     * @var ShipmentDataFactory
     */
    private $shipmentDataFactory;

    public function __construct(
        DPDBaltics $module,
        Language $language,
        ShipmentRepository $shipmentRepository,
        ShipmentHelper $shipmentHelper,
        ShipmentApiService $shipmentApiService,
        ShipmentDataValidator $shipmentDataValidator,
        ExceptionService $exceptionService,
        LabelPrintingService $labelPrintingService,
        PudoService $pudoService,
        OrderDeliveryTimeRepository $orderDeliveryTimeRepository,
        ShipmentDataFactory $shipmentDataFactory
    ) {
        $this->module = $module;
        $this->language = $language;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentHelper = $shipmentHelper;
        $this->shipmentApiService = $shipmentApiService;
        $this->shipmentDataValidator = $shipmentDataValidator;
        $this->exceptionService = $exceptionService;
        $this->labelPrintingService = $labelPrintingService;
        $this->pudoService = $pudoService;
        $this->orderDeliveryTimeRepository = $orderDeliveryTimeRepository;
        $this->shipmentDataFactory = $shipmentDataFactory;
    }

    public function createShipment(Order $order, $idProduct, $isTestMode, $numOfParcels, $weight, $goodsPrice)
    {
        $shipment = new DPDShipment();
        $shipment->id_order = $order->id;
        $shipment->printed_label = 0;
        $shipment->printed_manifest = 0;
        $shipment->manifest_closed = 0;
        $shipment->id_ws_manifest = '';
        $shipment->date_shipment = date('Y-m-d H:i:s');
        $shipment->id_service = $idProduct;
        $shipment->saved = 0;
        $shipment->is_test = $isTestMode;
        $shipment->label_format = Configuration::get(Config::DEFAULT_LABEL_FORMAT);
        $shipment->label_position = Configuration::get(Config::DEFAULT_LABEL_POSITION);
        $shipment->reference1 = $this->shipmentHelper->getReference($order->id, $order->reference);
        $shipment->num_of_parcels = $numOfParcels;
        $shipment->weight = $weight;
        $shipment->goods_price = $goodsPrice;

        $shipment->save();

        return $shipment;
    }

    public function createShipmentFromOrder(Order $order)
    {
        /** @var ShipmentRepository $shipmentRepository */
        $shipmentRepository = $this->module->getModuleContainer(ShipmentRepository::class);
        /** @var ProductRepository $productRepository */
        $productRepository = $this->module->getModuleContainer(ProductRepository::class);

        if ($shipmentRepository->hasAnyShipments($order->id)) {
            return true;
        }

        $parcelDistribution = Configuration::get(Config::PARCEL_DISTRIBUTION);

        $carrier = new Carrier($order->id_carrier, $this->language->id);
        $serviceCarrier = $productRepository->findProductByCarrierReference($carrier->id_reference);

        $idProduct = $serviceCarrier['id_dpd_product'];

        $isTestMode = Configuration::get(Config::SHIPMENT_TEST_MODE);

        if (DPDParcel::DISTRIBUTION_NONE === $parcelDistribution) {
            $result = $this->createNonDistributedShipment($order, $idProduct, $isTestMode);
        } elseif (DPDParcel::DISTRIBUTION_PARCEL_PRODUCT === $parcelDistribution) {
            $result = $this->createParcelDistributedShipments($order, $idProduct, $isTestMode);
        } elseif (DPDParcel::DISTRIBUTION_PARCEL_QUANTITY === $parcelDistribution) {
            $result =
                $this->createParcelQuantityDistributedShipments($order, $idProduct, $isTestMode);
        } else {
            $result = false;
        }

        return $result;
    }

    private function createNonDistributedShipment(Order $order, $idProduct, $isTestMode)
    {
        $products = $order->getProducts();
        $parcelPrice = 0;
        $parcelWeight = 0;
        foreach ($products as $product) {
            $parcelPrice += round($product['total_wt'], 2);
            $parcelWeight += $product['weight'] * $product['product_quantity'];
        }
        $shipment = $this->createShipment($order, $idProduct, $isTestMode, 1, $parcelWeight, $parcelPrice);

        if (!$shipment->id) {
            return false;
        }

        return true;
    }

    private function createParcelDistributedShipments(Order $order, $idProduct, $isTestMode)
    {
        $products = $order->getProducts();
        $parcelPrice = 0;
        $parcelWeight = 0;
        $parcelsNum = 0;
        foreach ($products as $product) {
            $parcelPrice += round($product['total_wt'], 2);
            $parcelWeight += $product['weight'] * $product['product_quantity'];
            $parcelsNum++;
            $shipment = $this->createShipment($order, $idProduct, $isTestMode, $parcelsNum, $parcelWeight, $parcelPrice);
        }



        if (!$shipment->id) {
            return false;
        }

        return true;
    }

    private function createParcelQuantityDistributedShipments(Order $order, $idProduct, $isTestMode)
    {
        $products = $order->getProducts();
        $parcelPrice = 0;
        $parcelWeight = 0;
        $parcelsNum = 0;
        foreach ($products as $product) {
            $parcelPrice += round($product['total_wt'], 2);
            $parcelWeight += $product['weight'] * $product['product_quantity'];
            $parcelsNum += $product['product_quantity'];
        }

        $shipment = $this->createShipment($order, $idProduct, $isTestMode, $parcelsNum, $parcelWeight, $parcelPrice);
        if (!$shipment->id) {
            return false;
        }
        return true;
    }

    public function createReturnServiceShipment($returnAddressId, $orderId)
    {
        if (!$returnAddressId) {
            return false;
        }
        /** @var ShipmentCreationResponse $shipmentResponse */
        $shipmentResponse = $this->shipmentApiService->createReturnServiceShipment($returnAddressId);

        if ($shipmentResponse->getStatus() !== Config::API_SUCCESS_STATUS) {
            throw new DPDBalticsAPIException(
                $shipmentResponse->getErrLog(),
                DPDBalticsAPIException::SHIPMENT_CREATION
            );
        }
        $dpdShipmentId = $this->shipmentRepository->getIdByOrderId($orderId);
        $dpdShipment = new DPDShipment($dpdShipmentId);
        $dpdShipment->return_pl_number = $shipmentResponse->getPlNumbersAsString();
        $dpdShipment->update();

        return $dpdShipment;
    }

    /**
     * @param Order $order
     * @param ShipmentData $shipmentData
     * @param false $print
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function saveShipment(Order $order, ShipmentData $shipmentData, $print = false)
    {
        $response['status'] = false;

        try {
            $this->shipmentDataValidator->validate($shipmentData);

        } catch (InvalidShipmentDataField $e) {

            $response['message'] = $this->exceptionService->getErrorMessageForException(
                $e,
                $this->exceptionService->getShipmentFieldErrorMessages()
            );
            return $response;

        } catch (Exception $e) {
            $response['message'] = $this->module->l(
                sprintf('Failed to save shipment data. Error: %s', $e->getMessage())
            );

            return $response;
        }

        $shipmentId = $this->shipmentRepository->getIdByOrderId($order->id);
        $shipment = new DPDShipment($shipmentId);

        if ($shipment->printed_label) {
            $labelFormat = Configuration::get(Config::DEFAULT_LABEL_FORMAT);
            $labelPosition = Configuration::get(Config::DEFAULT_LABEL_POSITION);

            return $this->labelPrintingService->setLabelOptions($shipmentId, $labelFormat, $labelPosition);
        }

        //Converts date from bootsrap date picker with zero upfront, because of validation error
        $dateWithZeroValues = date('Y-m-d h:i:s', strtotime($shipmentData->getDateShipment()));
        $shipmentData->setDateShipment($dateWithZeroValues);

        try {
            $this->shipmentRepository->saveShipment($shipmentData, $shipmentId);
        } catch (Exception $e) {
            $response['message'] = $this->module->l('Failed to save shipment');

            return $response;
        }
        if ($shipmentData->isPudo()) {

            $productId = $shipmentData->getProduct();
            $pudoId = $shipmentData->getSelectedPudoId();
            $isoCode = $shipmentData->getSelectedPudoIsoCode();
            $city = $shipmentData->getCity();
            $street = $shipmentData->getDpdStreet();
            $cartId = $order->id_cart;
            try {
                $this->pudoService->savePudoOrder($productId, $pudoId, $isoCode, $cartId, $city, $street);
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();

                return $response;
            }
        }

        if ($shipmentData->getDeliveryTime()) {
            $deliveryTimeId = $this->orderDeliveryTimeRepository->getOrderDeliveryIdByCartId($order->id_cart);
            $deliveryTime = new DPDOrderDeliveryTime($deliveryTimeId);
            $deliveryTime->delivery_time = $shipmentData->getDeliveryTime();
            $deliveryTime->update();
        }

        if ($print) {
            return $this->labelPrintingService->printAndSaveLabel($shipmentData, $shipmentId, $order->id);
        }

        $response['id_dpd_shipment'] = $shipmentId;
        $response['status'] = true;

        return $response;
    }

    /**
     * @param $orderId
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function formatLabelShipmentPrintResponse($orderId)
    {
        $order = new Order($orderId);
        $shipmentData = $this->shipmentDataFactory->getShipmentDataByIdOrder($orderId);

        return $this->saveShipment($order, $shipmentData, true);
    }

    /**
     * @param $orderIds
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function formatMultipleLabelShipmentPrintResponse($orderIds)
    {
        $response['status'] = false;

        $shipmentIds = [];
        $successfulOrders = [];

        $message = '';
        $success = true;
        foreach ($orderIds as $orderId) {
            $order = new Order($orderId);
            $shipmentData = $this->shipmentDataFactory->getShipmentDataByIdOrder($orderId);
            $response = $this->saveShipment($order, $shipmentData, true);
            if ($response['status']) {
                $shipmentIds[] = $response['id_dpd_shipment'];
                $successfulOrders[] = $orderId;
            }

            if (!$response['status']) {
                $message .= sprintf($this->module->l('Failed to save shipment for order %s Error: %s'), $orderId, $response['message']) . '</br>';
                $success = false;
            }
        }

        $response['status'] = $success;

        if ($success) {
            $message = $this->module->l('Labels printed successfully');
        }

        if (!$success) {
            $message .= sprintf(
                    $this->module->l('Printing failed for some orders, printed labels for orders: %s'),
                    implode(', ', $successfulOrders)
                ) . '</br>' . $message;
        }

        $response['message'] = $message;
        $response['shipment_ids'] = false;

        if (!empty($shipmentIds)) {
            $response['shipment_ids'] = json_encode($shipmentIds);
        }

        return $response;
    }
}
