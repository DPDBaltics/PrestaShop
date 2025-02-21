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
use Invertus\dpdBaltics\Exception\DpdCarrierException;
use Invertus\dpdBaltics\Provider\CurrentCountryProvider;
use Invertus\dpdBaltics\Repository\ParcelShopRepository;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Repository\PudoRepository;
use Invertus\dpdBaltics\Repository\ShipmentRepository;
use Invertus\dpdBaltics\Service\API\LabelApiService;
use Invertus\dpdBaltics\Service\API\ParcelShopSearchApiService;
use Invertus\dpdBaltics\Service\Exception\ExceptionService;
use Invertus\dpdBaltics\Service\GoogleApiService;
use Invertus\dpdBaltics\Service\Parcel\ParcelShopService;
use Invertus\dpdBaltics\Service\PudoService;
use Invertus\dpdBaltics\Service\ShipmentService;
use Invertus\dpdBalticsApi\Api\DTO\Response\ParcelShopSearchResponse;
use Invertus\dpdBalticsApi\Exception\DPDBalticsAPIException;
use Symfony\Component\HttpFoundation\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class DpdBalticsAjaxModuleFrontController extends ModuleFrontController
{
    const FILENAME = 'Ajax';

    /** @var  array */
    public $messages;

    /** @var DPDBaltics */
    public $module;

    public function checkAccess()
    {
        $isAjaxRequest = $this->isXmlHttpRequest();
        $isAjaxParameter = $this->ajax;
        $isTokenValid = $this->isTokenValid();
        $isParent = parent::checkAccess();
        $isCustomer = $this->context->customer->isLogged(true);

        $isAccessGranted = $isTokenValid &&
            $isAjaxParameter &&
            $isTokenValid &&
            $isParent &&
            $isAjaxRequest;

        if (!$isAccessGranted) {
            $error = $this->module->l('Unauthorized access', self::FILENAME);
            $this->messages[] = $error;
            $response = [
                'template' => $this->getMessageTemplate('danger'),
                'status' => false
            ];

            http_response_code(401);
            $this->ajaxRender(json_encode($response));
            exit;
        }

        return true;
    }

    public function init()
    {
        if (!$this->ajax) {
            Tools::redirect($this->context->link->getPageLink('pagenotfound'));
        }
        parent::init();
    }

    public function postProcess()
    {

        $action = Tools::getValue('action');
        $currentCountryProvider = $this->module->getModuleContainer('invertus.dpdbaltics.provider.current_country_provider');
        $countryCode = $currentCountryProvider->getCurrentCountryIsoCode();
        $city = Tools::getValue('city_name');
        $carrierId = (int)Tools::getValue('id_carrier');
        switch ($action) {
            case 'searchPudoServices':
                $cartId = Context::getContext()->cart->id;
                try {
                    $response = $this->searchPudoServices($countryCode, $city, $carrierId, $cartId);
                } catch (Exception $e) {
                    $this->messages[] = $this->module->l('Parcel shop search failed!');
                    $this->ajaxRender(json_encode(
                        [
                            'status' => false,
                            'template' => $this->getMessageTemplate('danger'),
                        ]
                    ));
                    exit;
                }
                $this->ajaxRender(json_encode($response));
                exit;
                break;
            case 'savePudoPickupPoint':
                $pudoId = Tools::getValue('id_pudo');
                $this->savePudoPickupPoint($pudoId, $countryCode);
                break;
            case 'validateOrderCustom':
                $response = false;
                /** @var \Invertus\dpdBaltics\Validate\Carrier\PudoValidate $pudoValidate */
                $pudoValidator = $this->module->getModuleContainer('invertus.dpdbaltics.validate.carrier.pudo_validate');
                /** @var  \Invertus\dpdBaltics\Validate\Phone\PhoneNumberValidator $phoneNumberValidator */
                $phoneNumberValidator = $this->module->getModuleContainer('invertus.dpdbaltics.validate.phone.phone_number_validator');
                try {
                    $carrier = new Carrier($carrierId);
                    $cartId = Context::getContext()->cart->id;

                    $phoneNumberValidator->isPhoneAddedInOrder($cartId);
                    $pudoValidator->isPudoSelected($cartId, $carrier->id_reference);
                } catch (DpdCarrierException $exception) {
                    $this->setErrorMessage($exception);
                    $this->ajaxRender(json_encode(
                        [
                            'status' => false,
                            'template' => $this->getMessageTemplate('danger'),
                            'pudo_valid' => $pudoValidator,
                            'phone_valid' => $phoneNumberValidator
                        ]
                    ));
                    exit;
                }
                $this->ajaxRender(json_encode(['status' => true]));
                exit;
                break;
            case 'updateStreetSelect':
                $city = Tools::getValue('city');
                $this->updateStreetSelect($countryCode, $city);
                break;
            case 'saveSelectedPhoneNumber':
                $cartId = Context::getContext()->cart->id;
                /** @var  \Invertus\dpdBaltics\Service\CarrierPhoneService $phoneService */
                $phoneService = $this->module->getModuleContainer('invertus.dpdbaltics.service.carrier_phone_service');
                /** @var  \Invertus\dpdBaltics\Validate\Phone\PhoneNumberValidator $phoneNumberValidator */
                $phoneNumberValidator = $this->module->getModuleContainer('invertus.dpdbaltics.validate.phone.phone_number_validator');
                $response = false;
                try {
                    $prefix = Tools::getValue('phone_area');
                    $phone = Tools::getValue('phone_number');
                    $phoneNumberValidator->isPhoneValid($prefix, $phone);
                    $response = $phoneService->saveCarrierPhone($cartId, $phone, $prefix);
                } catch (DpdCarrierException $exception) {
                    $this->setErrorMessage($exception);
                    $this->ajaxRender(json_encode(
                        [
                            'status' => false,
                            'template' => $this->getMessageTemplate('danger'),
                        ]
                    ));
                    exit;
                }
                $this->ajaxRender(json_encode(['status' => true]));
                exit;
                break;
            case 'updateParcelBlock':
                $street = Tools::getValue('street');
                $city = Tools::getValue('city');
                $cartId = Context::getContext()->cart->id;
                try {
                    $response = $this->searchPudoServices($countryCode, $city, $carrierId, $cartId, $street);
                } catch (Exception $e) {
                    $this->messages[] = $this->module->l('Parcel shop search failed!');
                    $this->ajaxRender(json_encode(
                        [
                            'status' => false,
                            'template' => $this->getMessageTemplate('danger'),
                        ]
                    ));
                    exit;
                }
                $this->ajaxRender(json_encode($response));
                exit;
                break;
            case 'saveSelectedStreet':
                $city = Tools::getValue('city');
                $street = Tools::getValue('street');
                $this->saveParcelShop($countryCode, $city, $street);
                break;
            default:
                break;
        }
        parent::postProcess();
    }

    private function searchPudoServices($countryCode, $city, $carrierId, $cartId, $street = '')
    {
        $carrier = new Carrier($carrierId);
        /** @var ParcelShopService $parcelShopService */
        /** @var ProductRepository $productRepo */
        $parcelShopService = $this->module->getModuleContainer('invertus.dpdbaltics.service.parcel.parcel_shop_service');
        $productRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.product_repository');

        $product = $productRepo->findProductByCarrierReference($carrier->id_reference);
        $isSameDayDelivery = ($product['product_reference'] === Config::PRODUCT_TYPE_SAME_DAY_DELIVERY);
        if ($isSameDayDelivery) {
            $city = Config::SAME_DAY_DELIVERY_CITY;
        }
        if ($street) {
            $parcelShops = $parcelShopService->getFilteredParcels($countryCode, $city, $street);
        } else {
            $parcelShops = $parcelShopService->getParcelShopsByCountryAndCity($countryCode, $city);
        }

        /** @var PudoService $pudoService */
        $pudoService = $this->module->getModuleContainer('invertus.dpdbaltics.service.pudo_service');

        $pudoServices = $pudoService->setPudoServiceTypes($parcelShops);
        $pudoServices = $pudoService->formatPudoServicesWorkHours($pudoServices);

        /** @var PudoRepository $pudoRepo */
        $pudoRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.pudo_repository');
        $pudoId = $pudoRepo->getIdByCart($cartId);
        $selectedPudo = new DPDPudo($pudoId);
        $coordinates = [];

        if (isset($parcelShops[0])) {
            $coordinates = [
                'lat' => $parcelShops[0]->getLatitude(),
                'lng' => $parcelShops[0]->getLongitude(),
            ];
        }

        $this->context->smarty->assign(
            [
                'carrierId' => $carrierId,
                'pickUpMap' => Configuration::get(Config::PICKUP_MAP),
                'pudoServices' => $pudoServices,
                'dpd_pickup_logo' => $this->module->getPathUri() . 'views/img/pickup.png',
                'dpd_locker_logo' => $this->module->getPathUri() . 'views/img/locker.png',
                'countryList' => Country::getCountries($this->context->language->id, true),
                'saved_pudo_id' => $selectedPudo->pudo_id
            ]
        );

        return [
            'template' => $this->context->smarty->fetch(
                $this->module->getLocalPath() . '/views/templates/hook/front/partials/markers-list.tpl'
            ),
            'status' => true,
            'coordinates' => json_encode($coordinates)
        ];
    }

    private function savePudoPickupPoint($pudoId, $countryCode)
    {
        /** @var PudoService $pudoService */
        $pudoService = $this->module->getModuleContainer('invertus.dpdbaltics.service.pudo_service');

        $addPudoCartOrderStatus = $pudoService->savePudoCartOrder(
            $this->context->cart->id_carrier,
            $this->context->cart->id,
            $pudoId,
            $countryCode
        );

        if (!$addPudoCartOrderStatus) {
            $this->messages[] = $this->l('Failed to save pickup point.');
            $this->ajaxRender(json_encode([
                'template' => $this->getMessageTemplate('danger'),
                'status' => false
            ]));
            exit;
        }
        $this->ajaxRender(json_encode([
            'status' => true
        ]));
        exit;
    }

    public function getFileName()
    {
        return self::FILENAME;
    }

    protected function getMessageTemplate(
        $type
    ) {
        $flashMessageTypes = ['success', 'info', 'warning', 'danger'];

        if (!in_array($type, $flashMessageTypes)) {
            $message = sprintf(
                'Invalid flash message type "%s" supplied. Available types are: %s',
                $type,
                implode(',', $flashMessageTypes)
            );
            throw new Exception($message);
        }

        $this->context->smarty->assign([
            'messageType' => $type,
            'messages' => $this->messages,
            'displayMessage' => true,
            'is_super_checkout' => Tools::getIsset('super_checkout_controller')
        ]);

        return $this->context->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/hook/front/partials/dpd-message.tpl'
        );
    }

    private function updateStreetSelect($countryCode, $city)
    {
        /** @var ParcelShopRepository $parcelShopRepo */
        $parcelShopRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.parcel_shop_repository');
        $streetList = $parcelShopRepo->getAllAddressesByCountryCodeAndCity($countryCode, $city);

        $this->context->smarty->assign(
            [
                'street_list' => $streetList,
                'show_shop_list' => Configuration::get(Config::PARCEL_SHOP_DISPLAY),
            ]
        );

        $streetSelectTpl = $this->context->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/hook/front/partials/pudo-search-street.tpl'
        );

        $this->ajaxRender(json_encode([
            'status' => true,
            'template' => $streetSelectTpl
        ]));
        exit;
    }

    private function saveParcelShop($countryCode, $city, $street)
    {
        /** @var PudoService $pudoService */
        $pudoService = $this->module->getModuleContainer('invertus.dpdbaltics.service.pudo_service');

        $cartId = $this->context->cart->id;
        $idCarrier = $this->context->cart->id_carrier;
        $isSuccess = $pudoService->saveSelectedParcel($cartId, $city, $street, $countryCode, $idCarrier);

        if (!$isSuccess) {
            $this->messages[] = $this->l('Failed to save pickup point.');
            $this->ajaxRender(json_encode([
                'template' => $this->getMessageTemplate('danger'),
                'status' => false
            ]));
            exit;
        }

        $pudoId = $pudoService->getPudoIdByCityAndAddress($city, $street);
        $parcelShops = $pudoService->getClosestParcelShops($pudoId);
        $coordinates = [];
        $selectedPudo = null;
        if (isset($parcelShops[0])) {
            $coordinates = [
                'lat' => $parcelShops[0]->getLatitude(),
                'lng' => $parcelShops[0]->getLongitude(),
            ];
            $selectedPudo = $parcelShops[0];
        }
        $pudoServices = $pudoService->setPudoServiceTypes($parcelShops);
        $pudoServices = $pudoService->formatPudoServicesWorkHours($pudoServices);

        $this->context->smarty->assign(
            [
//                'carrierId' => $carrierId,
                'pickUpMap' => Configuration::get(Config::PICKUP_MAP),
                'pudoId' => 1,
                'pudoServices' => $pudoServices,
                'dpd_pickup_logo' => $this->module->getPathUri() . 'views/img/pickup.png',
                'dpd_locker_logo' => $this->module->getPathUri() . 'views/img/locker.png',
                'countryList' => Country::getCountries($this->context->language->id, true),
                'selectedPudo' => $selectedPudo,
                'saved_pudo_id' => $selectedPudo->getParcelShopId()
            ]
        );

        $this->ajaxRender(json_encode([
            'template' => $this->context->smarty->fetch(
                $this->module->getLocalPath() . '/views/templates/hook/front/partials/markers-list.tpl'
            ),
            'status' => true,
            'selectedPudoId' => $selectedPudo->getParcelShopId(),
            'coordinates' => $coordinates
        ]));
        exit;
    }

    /**
     * @param Exception | DpdCarrierException $exception
     */
    private function setErrorMessage($exception)
    {
        switch ($exception->getMessage()) {
            case Config::ERROR_COULD_NOT_SAVE_PHONE_NUMBER:
                $this->messages[] = $this->module->l('Could not save phone number');
                break;
            case Config::ERROR_BAD_PHONE_NUMBER_PREFIX:
                $this->messages[] = $this->module->l('Phone number prefix is empty');
                break;
            case Config::ERROR_PHONE_EMPTY:
                $this->messages[] = $this->module->l('Phone number is empty');
                break;
            case Config::ERROR_PHONE_HAS_INVALID_CHARACTERS:
                $this->messages[] = $this->module->l('Phone number contains invalid characters');
                break;
            case Config::ERROR_PHONE_HAS_INVALID_LENGTH:
                $this->messages[] = $this->module->l('Phone number length is invalid');
                break;
            case Config::ERROR_INVALID_PUDO_TERMINAL:
                $this->messages[] = $this->module->l('Pudo point is missing, please select valid terminal point');
                break;
            default:
                $this->messages[] = $exception->getMessage();
        }
    }
}
