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

use Address;
use Country;
use DPDProduct;
use Invertus\dpdBaltics\Adapter\AddressAdapter;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\DTO\ShipmentData;
use Invertus\dpdBaltics\Repository\CodPaymentRepository;
use Invertus\dpdBaltics\Repository\OrderRepository;
use Invertus\dpdBaltics\Repository\PudoRepository;
use Invertus\dpdBaltics\Service\Parcel\ParcelShopService;
use Invertus\dpdBalticsApi\Api\DTO\Request\ShipmentCreationRequest;
use Invertus\dpdBalticsApi\Factory\APIRequest\ShipmentCreationFactory;
use Invertus\dpdBaltics\Service\Email\Handler\ParcelTrackingEmailHandler;
use Invertus\dpdBaltics\Util\StringUtility;
use Message;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShipmentApiService
{
    /**
     * @var ShipmentCreationFactory
     */
    private $shipmentCreationFactory;
    /**
     * @var CodPaymentRepository
     */
    private $codPaymentRepository;
    /**
     * @var ParcelTrackingEmailHandler
     */
    private $emailHandler;
    /**
     * @var ParcelShopService
     */
    private $parcelShopService;
    /**
     * @var AddressAdapter
     */
    private $addressAdapter;
    private $orderRepository;
    private $pudoRepository;

    public function __construct(
        ShipmentCreationFactory $shipmentCreationFactory,
        CodPaymentRepository $codPaymentRepository,
        ParcelTrackingEmailHandler $emailHandler,
        ParcelShopService $parcelShopService,
        AddressAdapter $addressAdapter,
        OrderRepository $orderRepository,
        PudoRepository $pudoRepository
    ) {
        $this->shipmentCreationFactory = $shipmentCreationFactory;
        $this->codPaymentRepository = $codPaymentRepository;
        $this->emailHandler = $emailHandler;
        $this->parcelShopService = $parcelShopService;
        $this->addressAdapter = $addressAdapter;
        $this->orderRepository = $orderRepository;
        $this->pudoRepository = $pudoRepository;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Invertus\dpdBaltics\Exception\ParcelEmailException
     * @throws \SmartyException
     */
    public function createShipment($addressId, ShipmentData $shipmentData, $orderId)
    {
        $address = new Address($addressId);

        $isCompany = trim($shipmentData->getCompany()) ? true : false;
        $firstName = $shipmentData->getName();
        if ($isCompany) {
            $firstName = $shipmentData->getCompany();
        }
        $phoneNumber = $shipmentData->getPhoneArea() . $shipmentData->getPhone();
        $dpdProduct = new DPDProduct($shipmentData->getProduct());
        $parcelType = $dpdProduct->getProductReference();
        $country = Country::getIsoById($address->id_country);
        $postCode = $address->postcode;
        $hasAddressFields = (bool) !$postCode || !$firstName || !$address->city || !$country;

        // Post code might be wrong in order adress, so we set terminal post code instead
        $selectedParcel = null;
        if ($shipmentData->isPudo()) {
            $parcel = $this->parcelShopService->getParcelShopByShopId($shipmentData->getSelectedPudoId());
            $selectedParcel = is_array($parcel) && !empty($parcel) ? reset($parcel) : $parcel;
            if ($selectedParcel && is_object($selectedParcel)) {
                $postCode = $selectedParcel->getPCode();
                $address->address1 = $selectedParcel->getStreet();
            }
        }

        // IF prestashop allows, we take selected parcel terminal address in case information is missing in checkout address in specific cases.
        if (($hasAddressFields) && $shipmentData->isPudo() && $selectedParcel && is_object($selectedParcel)) {
            $firstName = $selectedParcel->getCompany();
            $address->address1 = $selectedParcel->getStreet();
            $address->city = $selectedParcel->getCity();
            $country = $selectedParcel->getCountry();
        }

        $postCode = $this->addressAdapter->formatPostCodeByCountry($postCode, $country);

        $shipmentCreationRequest = new ShipmentCreationRequest(
            $firstName,
            $address->address1,
            $address->city,
            $country,
            $postCode,
            $shipmentData->getParcelAmount(),
            $parcelType,
            $phoneNumber,
            $shipmentData->getEmail(),
            1
        );

        if (!$isCompany) {
            $shipmentCreationRequest->setName2($address->lastname);
        }

        $shipmentCreationRequest = $this->setNotRequiredData($shipmentCreationRequest, $shipmentData, $parcelType);

        if ($dpdProduct->is_cod) {
            $shipmentCreationRequest->setCodAmount($shipmentData->getGoodsPrice());
        }

        $cartMessage = Message::getMessagesByOrderId($orderId);

        if ($cartMessage)
        {
            $trimmedRemarkMessage = StringUtility::trimString($cartMessage[0]['message']);
            $shipmentCreationRequest->setRemark(StringUtility::removeSpecialCharacters($trimmedRemarkMessage));
        }

        if ($dpdProduct->is_pudo && $shipmentData->getSelectedPudoId()) {
            $shipmentCreationRequest = $this->setPudoData($shipmentCreationRequest, $shipmentData);
        }

        if (!$dpdProduct->is_pudo && $shipmentData->isDpdDocumentReturn()) {
            $shipmentCreationRequest->setParcelType($parcelType . Config::DOCUMENT_RETURN_CODE);
            $shipmentCreationRequest->setDnoteReference($shipmentData->getDpdDocumentReturnNumber());
        }

        if (Config::productHasDeliveryTime($parcelType) && $shipmentData->getDeliveryTime()) {
            $timeFrames = explode('-', $shipmentData->getDeliveryTime());
            if (count($timeFrames) === 2) {
                // Format should be HH:mm (e.g., "18:00", "22:00")
                $timeFrom = trim($timeFrames[0]);
                $timeTo = trim($timeFrames[1]);

                // Validate HH:mm format
                if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $timeFrom) &&
                    preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $timeTo)) {
                    $shipmentCreationRequest->setTimeFrameFrom($timeFrom);
                    $shipmentCreationRequest->setTimeFrameTo($timeTo);
                }
            }
        }
        $shipmentCreator = $this->shipmentCreationFactory->makeShipmentCreation();

        $shipmentResponse = $shipmentCreator->createShipment($shipmentCreationRequest);

        if ($shipmentResponse->getStatus() === "ok" && $this->isTrackingEmailAllowed()) {
            $this->emailHandler->handle($orderId, $shipmentResponse->getPlNumber());
        }

        return $shipmentResponse;
    }

    public function createReturnServiceShipment($addressTemplateId, $orderId, ShipmentData $shipmentData)
    {
        $order = new \Order($orderId);

        $address = new \Address($order->id_address_delivery);
        $customer = new \Customer($order->id_customer);

        /** @var array $phoneNumber */
        $phoneNumber = $this->orderRepository->getPhoneByIdCart($order->id_cart);

        if ($shipmentData->isPudo()) {
            $selectedPudo = $this->pudoRepository->getDPDPudo($shipmentData->getIdPudo());

            $address1 = $selectedPudo->street;
            $city = $selectedPudo->city;
            $countryIso = $selectedPudo->country_code;
            $postCode = preg_replace('/[^0-9]/', '', $selectedPudo->post_code);
        } else {
            $address1 = $address->address1;
            $city = $address->city;
            $countryIso = Country::getIsoById($address->id_country);
            $postCode = preg_replace('/[^0-9]/', '', $address->postcode);
        }

        $parcelType = 'RET-RETURN';

        $shipmentCreationRequest = new ShipmentCreationRequest(
            $address->firstname . ' ' . $address->lastname,
            $address1,
            $city,
            $countryIso,
            $postCode,
            '1',
            $parcelType,
            $phoneNumber['phone_area'] . $phoneNumber['phone'],
            $customer->email,
            1
        );
        $shipmentCreationRequest = $this->setNotRequiredData($shipmentCreationRequest, $shipmentData, $parcelType);

        $shipmentCreator = $this->shipmentCreationFactory->makeShipmentCreation();

        return $shipmentCreator->createShipment($shipmentCreationRequest);
    }

    private function setNotRequiredData(ShipmentCreationRequest $shipmentCreationRequest, ShipmentData $shipmentData, $parcelType)
    {
        $shipmentCreationRequest->setOrderNumber($shipmentData->getReference1());
        $shipmentCreationRequest->setOrderNumber1($shipmentData->getReference2());
        $shipmentCreationRequest->setOrderNumber2($shipmentData->getReference3());
        $shipmentCreationRequest->setOrderNumber3($shipmentData->getReference4());
        $shipmentCreationRequest->setWeight($shipmentData->getWeight());

        if (Config::productHasDeliveryTime($parcelType)) {
            $shipmentCreationRequest->setIdmSmsNumber($shipmentData->getPhone());
            $shipmentCreationRequest->setPredict('y');
        }

        return $shipmentCreationRequest;
    }

    private function setPudoData(ShipmentCreationRequest $shipmentCreationRequest, ShipmentData $shipmentData)
    {
        $shipmentCreationRequest->setParcelShopId($shipmentData->getSelectedPudoId());

        return $shipmentCreationRequest;
    }

    private function isTrackingEmailAllowed()
    {
        return (bool) \Configuration::get(Config::SEND_EMAIL_ON_PARCEL_CREATION);
    }

}
