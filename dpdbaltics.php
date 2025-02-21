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

use Invertus\dpdBaltics\Builder\Template\Front\CarrierOptionsBuilder;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\Grid\LinkRowActionCustom;
use Invertus\dpdBaltics\Grid\SubmitBulkActionCustom;
use Invertus\dpdBaltics\OnBoard\Service\OnBoardService;
use Invertus\dpdBaltics\Repository\AddressTemplateRepository;
use Invertus\dpdBaltics\Repository\OrderRepository;
use Invertus\dpdBaltics\Repository\ParcelShopRepository;
use Invertus\dpdBaltics\Repository\PhonePrefixRepository;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Repository\PudoRepository;
use Invertus\dpdBaltics\Repository\ReceiverAddressRepository;
use Invertus\dpdBaltics\Repository\ShipmentRepository;
use Invertus\dpdBaltics\Repository\ZoneRepository;
use Invertus\dpdBaltics\Service\CarrierPhoneService;
use Invertus\dpdBaltics\Service\Exception\ExceptionService;
use Invertus\dpdBaltics\Service\GoogleApiService;
use Invertus\dpdBaltics\Service\Label\LabelPositionService;
use Invertus\dpdBaltics\Service\OrderService;
use Invertus\dpdBaltics\Service\Parcel\ParcelShopService;
use Invertus\dpdBaltics\Service\Payment\PaymentService;
use Invertus\dpdBaltics\Service\PudoService;
use Invertus\dpdBaltics\Service\ShipmentService;
use Invertus\dpdBaltics\Service\ShippingPriceCalculationService;
use Invertus\dpdBaltics\Service\TabService;
use Invertus\dpdBaltics\Service\TrackingService;
use Invertus\dpdBaltics\Util\CountryUtility;
use Invertus\dpdBaltics\Validate\Carrier\PudoValidate;
use Invertus\dpdBalticsApi\Api\DTO\Response\ParcelPrintResponse;
use Invertus\dpdBalticsApi\Api\DTO\Response\ParcelShopSearchResponse;
use Invertus\dpdBalticsApi\Exception\DPDBalticsAPIException;
use Invertus\dpdBalticsApi\Factory\SerializerFactory;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

if (!defined('_PS_VERSION_')) {
    exit;
}


class DPDBaltics extends CarrierModule
{
    /**
     * Symfony DI Container
     **/
    private $moduleContainer;

    /**
     * Prestashop fills this property automatically with selected carrier ID in FO checkout
     *
     * @var int $id_carrier
     */
    public $id_carrier;


    public function __construct()
    {
        $this->name = 'dpdbaltics';
        $this->displayName = $this->l('DPDBaltics');
        $this->author = 'Invertus';
        $this->tab = 'shipping_logistics';
        $this->description = 'DPD Baltics shipping integration';
        $this->version = '3.2.21';
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];
        $this->need_instance = 0;
        parent::__construct();

        $this->autoLoad();
        $this->compile();
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        /** @var \Invertus\dpdBaltics\Infrastructure\Bootstrap\Install\Installer $installer */
        $installer = $this->getService(\Invertus\dpdBaltics\Infrastructure\Bootstrap\Install\Installer::class);

        try {
            $installer->init();

            return true;
        } catch (\Invertus\dpdBaltics\Infrastructure\Bootstrap\Exception\CouldNotInstallModule $exception) {
            $this->_errors[] = $exception->getMessage();

            return false;
        }
    }

    public function uninstall()
    {
        $uninstall = parent::uninstall();

        if (!$uninstall) {
            return false;
        }

        /** @var \Invertus\dpdBaltics\Infrastructure\Bootstrap\Uninstall\Uninstaller $uninstaller */
        $uninstaller = $this->getService(\Invertus\dpdBaltics\Infrastructure\Bootstrap\Uninstall\Uninstaller::class);

        try {
            $uninstaller->init();

            return true;
        } catch (\Invertus\dpdBaltics\Infrastructure\Bootstrap\Exception\CouldNotUninstallModule $exception) {
            $this->_errors[] = $exception->getMessage();

            return false;
        }
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(\Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs::ADMIN_SETTINGS_CONTROLLER));
    }

    public function getService($serviceName)
    {
        return $this->getModuleContainer()->get($serviceName);
    }

    /**
     * @return mixed
     */
    public function getModuleContainer($id = false)
    {
        if ($id) {
            return $this->moduleContainer->get($id);
        }

        return $this->moduleContainer;
    }

    public function hookActionFrontControllerSetMedia()
    {
        //TODO fillup this array when more modules are compatible with OPC
        $onePageCheckoutControllers = ['supercheckout', 'onepagecheckoutps', 'thecheckout'];
        $applicableControlelrs = ['order', 'order-opc', 'ShipmentReturn', 'supercheckout'];
        $currentController = !empty($this->context->controller->php_self) ? $this->context->controller->php_self : Tools::getValue('controller');

        if ('product' === $currentController) {
            $this->context->controller->registerStylesheet(
                'dpdbaltics-product-carriers.',
                'modules/' . $this->name . '/views/css/front/product-carriers.css',
                [
                    'media' => 'all',
                    'position' => 150
                ]
            );
        }

        /** @var \Invertus\dpdBaltics\Validate\Compatibility\OpcModuleCompatibilityValidator $opcModuleCompatibilityValidator */
        $opcModuleCompatibilityValidator = $this->getModuleContainer('invertus.dpdbaltics.validator.opc_module_compatibility_validator');

        if (in_array($currentController, $onePageCheckoutControllers, true) || $opcModuleCompatibilityValidator->isOpcModuleInUse()) {
            $this->context->controller->addJqueryPlugin('chosen');

            $this->context->controller->registerJavascript(
                'dpdbaltics-opc',
                'modules/' . $this->name . '/views/js/front/order-opc.js',
                [
                    'position' => 'bottom',
                    'priority' => 130
                ]
            );

            $this->context->controller->registerJavascript(
                'dpdbaltics-supercheckout',
                'modules/' . $this->name . '/views/js/front/modules/supercheckout.js',
                [
                        'position' => 'bottom',
                        'priority' => 130
                    ]
            );

            $this->context->controller->registerStylesheet(
                'dpdbaltics-opc',
                'modules/' . $this->name . '/views/css/front/onepagecheckout.css',
                [
                    'position' => 'bottom',
                    'priority' => 130
                ]
            );

            Media::addJsDef([
                'dpdbaltics' => [
                    'isOnePageCheckout' => $opcModuleCompatibilityValidator->isOpcModuleInUse()
                ]
            ]);
        }

        /** @var \Invertus\dpdBaltics\Provider\CurrentCountryProvider $currentCountryProvider */
        $currentCountryProvider = $this->getModuleContainer('invertus.dpdbaltics.provider.current_country_provider');
        $webServiceCountryCode = Configuration::get(Config::WEB_SERVICE_COUNTRY);
        $carrierIds = [];
        $baseUrl = $this->context->shop->getBaseURL(true, false);

        if ($webServiceCountryCode === Config::LATVIA_ISO_CODE || $currentController === 'supercheckout') {
            /** @var ProductRepository $productRepo */
            $productRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.product_repository');

            $dpdProductReferences = $productRepo->getAllActiveDpdProductReferences();

            foreach ($dpdProductReferences as $reference) {
                $carrier = Carrier::getCarrierByReference($reference['id_reference']);
                if (Validate::isLoadedObject($carrier)) {
                    $carrierIds[] = $carrier->id;
                }
            }
        }

        Media::addJsDef([
            'lapinas_img' => $baseUrl . $this->getPathUri() . 'views/img/lapinas.png',
            'lapinas_text' => $this->module->l('Sustainable'),
            'dpd_carrier_ids' => $carrierIds
        ]);
        if (in_array($currentController, $applicableControlelrs, true)) {
            Media::addJsDef([
                'select_an_option_translatable' => $this->l('Select an Option'),
                'select_an_option_multiple_translatable' => $this->l('Select Some Options'),
                'no_results_translatable' => $this->l('No results match'),
            ]);
            $this->context->controller->addJS($this->getPathUri() . 'views/js/front/order.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/front/order-input.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/front/sustainable-logo.js');
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/front/order-input.css');
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/front/sustainable-logo.css');
            /** @var PaymentService $paymentService */
            $paymentService = $this->getModuleContainer('invertus.dpdbaltics.service.payment.payment_service');
            $isPickupMap = Configuration::get(\Invertus\dpdBaltics\Config\Config::PICKUP_MAP);
            $cart = Context::getContext()->cart;
            $paymentService->filterPaymentMethods($cart);
            $paymentService->filterPaymentMethodsByCod($cart);

            /** @var ProductRepository $productRepo */
            $productRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.product_repository');
            Media::addJsDef([
                'pudoCarriers' => json_encode($productRepo->getPudoProducts()),
                'currentController' => $currentController,
                'is_pickup_map'  => $isPickupMap,
                'id_language' => $this->context->language->id,
                'id_shop' => $this->context->shop->id,
                'dpdAjaxLoaderPath' => $this->getPathUri() . 'views/img/ajax-loader-big.gif',
                'dpdPickupMarkerPath' => $this->getPathUri() . 'views/img/dpd-pick-up.png',
                'dpdLockerMarkerPath' => $this->getPathUri() . 'views/img/locker.png',
                'dpdHookAjaxUrl' => $this->context->link->getModuleLink($this->name, 'Ajax'),
                'pudoSelectSuccess' => $this->l('Pick-up point selected'),
                'dpd_carrier_ids' => $carrierIds,
            ]);

            $this->context->controller->registerStylesheet(
                'dpdbaltics-pudo-shipment',
                'modules/' . $this->name . '/views/css/front/' . 'pudo-shipment.css',
                [
                    'media' => 'all',
                    'position' => 150
                ]
            );
            if ($isPickupMap) {
                /** @var GoogleApiService $googleApiService */
                $googleApiService = $this->getModuleContainer('invertus.dpdbaltics.service.google_api_service');
                $this->context->controller->registerJavascript(
                    'dpdbaltics-google-api',
                    $googleApiService->getFormattedGoogleMapsUrl(),
                    [
                        'server' => 'remote'
                    ]
                );
            }
            $this->context->controller->registerJavascript(
                'dpdbaltics-pudo',
                'modules/' . $this->name . '/views/js/front/pudo.js',
                [
                    'position' => 'bottom',
                    'priority' => 130
                ]
            );
            $this->context->controller->registerJavascript(
                'dpdbaltics-pudo-search',
                'modules/' . $this->name . '/views/js/front/pudo-search.js',
                [
                    'position' => 'bottom',
                    'priority' => 130
                ]
            );
        }
    }

    public function hookActionValidateStepComplete(&$params)
    {
        if ('delivery' !== $params['step_name']) {
            return;
        }

        /** @var Cart $cart */
        $cart = $params['cart'];
        $carrier = new Carrier($cart->id_carrier);
        $idShop = $this->context->shop->id;

        /** @var Invertus\dpdBaltics\Repository\CarrierRepository $carrierRepo */
        /** @var Invertus\dpdBaltics\Repository\ProductRepository $productRepo */
        $carrierRepo = $this->getModuleContainer()->get('invertus.dpdbaltics.repository.carrier_repository');
        $productRepo = $this->getModuleContainer()->get('invertus.dpdbaltics.repository.product_repository');

        $carrierReference = $carrier->id_reference;
        $dpdCarriers = $carrierRepo->getDpdCarriers($idShop);
        $isDpdCarrier = false;
        foreach ($dpdCarriers as $dpdCarrier) {
            if ($carrierReference == $dpdCarrier['id_reference']) {
                $isDpdCarrier = true;
                $productId = $productRepo->getProductIdByCarrierReference($carrier->id_reference);
                $product = new DPDProduct($productId);
                $isSameDayDelivery = $product->product_reference === Config::PRODUCT_TYPE_SAME_DAY_DELIVERY;
                break;
            }
        }
        if (!$isDpdCarrier) {
            return true;
        }

        if ($isSameDayDelivery) {
            /** @var PudoRepository $pudoRepo */
            $pudoRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.pudo_repository');
            $pudoId = $pudoRepo->getIdByCart($cart->id);
            $selectedPudo = new DPDPudo($pudoId);
            if ($selectedPudo->city !== Config::SAME_DAY_DELIVERY_CITY) {
                $this->context->controller->errors[] =
                    $this->l('This carrier can\'t deliver to your selected city');
                $params['completed'] = false;
                $selectedPudo->delete();

                return;
            }
        }

        //NOTE: thecheckout  triggers this hook without phone parameters the phone is saved with ajax request
        if (Tools::getValue('module') === 'thecheckout') {
            return;
        }

        if (!Tools::getValue('dpd-phone')) {
            $this->context->controller->errors[] =
                $this->l('In order to use DPD Carrier you need to enter phone number');
            $params['completed'] = false;

            return;
        }

        if (!Tools::getValue('dpd-phone-area')) {
            $this->context->controller->errors[] =
                $this->l('In order to use DPD Carrier you need to enter phone area');
            $params['completed'] = false;

            return;
        }

        /** @var CarrierPhoneService $carrierPhoneService */
        $carrierPhoneService = $this->getModuleContainer()->get('invertus.dpdbaltics.service.carrier_phone_service');

        try {
            $carrierPhoneService->saveCarrierPhone(
                $this->context->cart->id,
                Tools::getValue('dpd-phone'),
                Tools::getValue('dpd-phone-area')
            );
        } catch (Exception $exception) {
            if ($exception->getCode() === Config::ERROR_COULD_NOT_SAVE_PHONE_NUMBER) {
                $this->context->controller->errors[] = $this->l('Phone data is not saved');
                $params['completed'] = false;
            }
        }
        /** @var \Invertus\dpdBaltics\Service\OrderDeliveryTimeService $orderDeliveryService */
        $orderDeliveryService = $this->getModuleContainer()->get('invertus.dpdbaltics.service.order_delivery_time_service');

        $deliveryTime = Tools::getValue('dpd-delivery-time');
        if ($deliveryTime) {
            if (!$orderDeliveryService->saveDeliveryTime(
                $this->context->cart->id,
                $deliveryTime
            )) {
                $this->context->controller->errors[] = $this->l('Delivery time data is not saved');
                $params['completed'] = false;
            };
        }

        /** @var Cart $cart */
        $cart = $params['cart'];
        $carrier = new Carrier($cart->id_carrier);
        /** @var PudoValidate $pudoValidator */
        $pudoValidator = $this->getModuleContainer('invertus.dpdbaltics.validate.carrier.pudo_validate');
        if (!$pudoValidator->validatePickupPoints($cart->id, $carrier->id_reference)) {
            $carrier = new Carrier($cart->id_carrier, $this->context->language->id);
            $this->context->controller->errors[] =
                sprintf($this->l('Please select pickup point for carrier: %s.'), $carrier->name);

            $params['completed'] = false;
        }
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if ($params['type'] == 'after_price') {
            /** @var CarrierOptionsBuilder $carrierOptionsBuilder */
            $carrierOptionsBuilder = $this->getModuleContainer()->get('invertus.dpdbaltics.builder.template.front.carrier_options_builder');

            return $carrierOptionsBuilder->renderCarrierOptionsInProductPage();
        }
    }

    public function hookDisplayBackOfficeTop()
    {
        if ($this->context->controller instanceof AbstractAdminController &&
            Configuration::get(Config::ON_BOARD_TURNED_ON) &&
            Configuration::get(Config::ON_BOARD_STEP)
        ) {
            /** @var OnBoardService $onBoardService */
            $onBoardService = $this->getModuleContainer('invertus.dpdbaltics.on_board.service.on_board_service');
            return $onBoardService->makeStepActionWithTemplateReturn();
        }
    }

    public function getOrderShippingCost($cart, $shippingCost)
    {
        return $this->getOrderShippingCostExternal($cart);
    }

    /**
     * @param $cart Cart
     * @return bool|float|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getOrderShippingCostExternal($cart)
    {
        // This method is still called when module is disabled so we need to do a manual check here
        if (!$this->active) {
            return false;
        }

        if ($this->context->controller->ajax && Tools::getValue('id_address_delivery')) {
            $cart->id_address_delivery = (int)Tools::getValue('id_address_delivery');
        }

        /** @var ZoneRepository $zoneRepository */
        /** @var ProductRepository $productRepo */
        /** @var \Invertus\dpdBaltics\Service\Product\ProductAvailabilityService $productAvailabilityService */
        /** @var \Invertus\dpdBaltics\Validate\Weight\CartWeightValidator $cartWeightValidator */
        /** @var \Invertus\dpdBaltics\Provider\CurrentCountryProvider $currentCountryProvider */
        $zoneRepository =  $this->getModuleContainer('invertus.dpdbaltics.repository.zone_repository');
        $productRepo = $this->getModuleContainer()->get('invertus.dpdbaltics.repository.product_repository');
        $productAvailabilityService = $this->getModuleContainer('invertus.dpdbaltics.service.product.product_availability_service');
        $cartWeightValidator = $this->getModuleContainer('invertus.dpdbaltics.validate.weight.cart_weight_validator');
        $currentCountryProvider = $this->getModuleContainer('invertus.dpdbaltics.provider.current_country_provider');

        $deliveryAddress = new Address($cart->id_address_delivery);

        if (empty($zoneRepository->findZoneInRangeByAddress($deliveryAddress))) {
            return false;
        }

        $carrier = new Carrier($this->id_carrier);

        if (!$productAvailabilityService->checkIfCarrierIsAvailable($carrier->id_reference)) {
            return false;
        }

        if (!$productRepo->checkIfCarrierIsAvailableInCountry((int) $carrier->id_reference, (int) $deliveryAddress->id_country)
        ) {
            return false;
        }

        try {
            $isCarrierAvailableInShop = $productRepo->checkIfCarrierIsAvailableInShop($carrier->id_reference, $this->context->shop->id);
            if (empty($isCarrierAvailableInShop)) {
                return false;
            }

            $serviceCarrier = $productRepo->findProductByCarrierReference($carrier->id_reference);
        } catch (Exception $e) {
            $tplVars = [
                'errorMessage' => $this->l('Something went wrong while collecting DPD carrier data'),
            ];
            $this->context->smarty->assign($tplVars);

            return $this->context->smarty->fetch(
                $this->getLocalPath() . 'views/templates/admin/dpd-shipment-fatal-error.tpl'
            );
        }

        if ((bool)$serviceCarrier['is_home_collection']) {
            return false;
        }

        $countryCode = $currentCountryProvider->getCurrentCountryIsoCode($cart);

        $parcelDistribution = \Configuration::get(Config::PARCEL_DISTRIBUTION);
        $maxAllowedWeight = Config::getDefaultServiceWeights($countryCode, $serviceCarrier['product_reference']);

        if (!$cartWeightValidator->validate($cart, $parcelDistribution, $maxAllowedWeight)) {
            return false;
        }

        if ($serviceCarrier['product_reference'] === Config::PRODUCT_TYPE_SAME_DAY_DELIVERY) {
            $isSameDayAvailable = \Invertus\dpdBaltics\Util\ProductUtility::validateSameDayDelivery(
                $countryCode,
                $deliveryAddress->city
            );

            if (!$isSameDayAvailable) {
                return false;
            }
        }

        /** @var ShippingPriceCalculationService $shippingPriceCalculationService */
        $shippingPriceCalculationService = $this->getModuleContainer()->get('invertus.dpdbaltics.service.shipping_price_calculation_service');

        return $shippingPriceCalculationService->calculate($cart, $carrier, $deliveryAddress);
    }

    public function hookDisplayCarrierExtraContent($params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];
        $carrier = new Carrier($params['carrier']['id']);

        /** @var \Invertus\dpdBaltics\Provider\CurrentCountryProvider $currentCountryProvider */
        $currentCountryProvider = $this->getModuleContainer('invertus.dpdbaltics.provider.current_country_provider');
        $countryCode = $currentCountryProvider->getCurrentCountryIsoCode($cart);

        $deliveryAddress = new Address($cart->id_address_delivery);

        /** @var CarrierPhoneService $carrierPhoneService */
        /** @var \Invertus\dpdBaltics\Presenter\DeliveryTimePresenter $deliveryTimePresenter */
        /** @var ProductRepository $productRepo */
        $carrierPhoneService = $this->getModuleContainer()->get('invertus.dpdbaltics.service.carrier_phone_service');
        $deliveryTimePresenter = $this->getModuleContainer()->get('invertus.dpdbaltics.presenter.delivery_time_presenter');
        $productRepo = $this->getModuleContainer()->get('invertus.dpdbaltics.repository.product_repository');

        $productId = $productRepo->getProductIdByCarrierReference($carrier->id_reference);
        $dpdProduct = new DPDProduct($productId);
        $return = '';
        if ($dpdProduct->getProductReference() === Config::PRODUCT_TYPE_SAME_DAY_DELIVERY) {
            /** @var \Invertus\dpdBaltics\Presenter\SameDayDeliveryMessagePresenter $sameDayDeliveryPresenter */
            $sameDayDeliveryPresenter = $this->getModuleContainer()->get('invertus.dpdbaltics.presenter.same_day_delivery_message_presenter');
            $return .= $sameDayDeliveryPresenter->getSameDayDeliveryMessageTemplate();
        }
        $return .= $carrierPhoneService->getCarrierPhoneTemplate($this->context->cart->id, $carrier->id_reference);
        if ($dpdProduct->getProductReference() === Config::PRODUCT_TYPE_B2B ||
            $dpdProduct->getProductReference() === Config::PRODUCT_TYPE_B2B_COD
        ) {
            $return .= $deliveryTimePresenter->getDeliveryTimeTemplate($countryCode, $deliveryAddress->city);
        }

        /** @var ProductRepository $productRep */
        $productRep = $this->getModuleContainer('invertus.dpdbaltics.repository.product_repository');
        $isPudo = $productRep->isProductPudo($carrier->id_reference);
        if ($isPudo) {
            /** @var PudoRepository $pudoRepo */
            /** @var ParcelShopRepository $parcelShopRepo */
            /** @var ProductRepository $productRepo */
            $pudoRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.pudo_repository');
            $parcelShopRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.parcel_shop_repository');
            $productRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.product_repository');
            $product = $productRepo->findProductByCarrierReference($carrier->id_reference);
            $isSameDayDelivery = ($product['product_reference'] === Config::PRODUCT_TYPE_SAME_DAY_DELIVERY);

            $pudoId = $pudoRepo->getIdByCart($cart->id);
            $selectedPudo = new DPDPudo($pudoId);

            /** @var ParcelShopService $parcelShopService */
            $parcelShopService= $this->getModuleContainer('invertus.dpdbaltics.service.parcel.parcel_shop_service');

            $selectedCity = null;
            $selectedStreet = null;

            try {
                if (Validate::isLoadedObject($selectedPudo) && !$isSameDayDelivery) {
                    $selectedCity = $selectedPudo->city;
                    $selectedStreet = $selectedPudo->street;
                    $parcelShops = $parcelShopService->getParcelShopsByCountryAndCity($countryCode, $selectedCity);
                    $parcelShops = $parcelShopService->moveSelectedShopToFirst($parcelShops, $selectedStreet);
                } else {
                    $selectedCity = $deliveryAddress->city;
                    $selectedStreet = $deliveryAddress->address1;
                    $parcelShops = $parcelShopService->getParcelShopsByCountryAndCity($countryCode, $selectedCity);
                    $parcelShops = $parcelShopService->moveSelectedShopToFirst($parcelShops, $selectedStreet);
                    if (!$parcelShops) {
                        $selectedCity = null;
                    }
                }
            } catch (DPDBalticsAPIException $e) {
                /** @var ExceptionService $exceptionService */
                $exceptionService = $this->getModuleContainer('invertus.dpdbaltics.service.exception.exception_service');
                $tplVars = [
                    'errorMessage' => $exceptionService->getErrorMessageForException(
                        $e,
                        $exceptionService->getAPIErrorMessages()
                    )
                ];
                $this->context->smarty->assign($tplVars);

                return $this->context->smarty->fetch(
                    $this->getLocalPath() . 'views/templates/admin/dpd-shipment-fatal-error.tpl'
                );
            } catch (Exception $e) {
                $tplVars = [
                    'errorMessage' => $this->l("Something went wrong. We couldn't find parcel shops."),
                ];
                $this->context->smarty->assign($tplVars);

                return $this->context->smarty->fetch(
                    $this->getLocalPath() . 'views/templates/admin/dpd-shipment-fatal-error.tpl'
                );
            }

            /** @var PudoService $pudoService */
            $pudoService = $this->getModuleContainer('invertus.dpdbaltics.service.pudo_service');

            $pudoServices = $pudoService->setPudoServiceTypes($parcelShops);
            $pudoServices = $pudoService->formatPudoServicesWorkHours($pudoServices);

            if (isset($parcelShops[0])) {
                $coordinates = [
                    'lat' => $parcelShops[0]->getLatitude(),
                    'lng' => $parcelShops[0]->getLongitude(),
                ];
                $this->context->smarty->assign(
                    [
                        'coordinates' => $coordinates
                    ]
                );
            }

            if ($isSameDayDelivery) {
                $cityList['Rīga'] = 'Rīga';
            } else {
                $cityList = $parcelShopRepo->getAllCitiesByCountryCode($countryCode);
            }

            if (!in_array($selectedCity, $cityList) && isset($parcelShops[0])) {
                $selectedCity = $parcelShops[0]->getCity();
            }

            if (!$selectedCity && Tools::getValue('controller') !== 'order') {
                $tplVars = [
                    'displayMessage' => true,
                    'messages' => [$this->l("Your delivery address city is not in a list of pickup cities, please select closest pickup point city below manually")],
                    'messageType_pudo' => 'danger'

                ];
                $this->context->smarty->assign($tplVars);
            }

            $streetList = $parcelShopRepo->getAllAddressesByCountryCodeAndCity($countryCode, $selectedCity);
            $this->context->smarty->assign(
                [
                    'currentController' => Tools::getValue('controller'),
                    'carrierId' => $carrier->id,
                    'pickUpMap' => Configuration::get(Config::PICKUP_MAP),
                    'pudoId' => $pudoId,
                    'pudoServices' => $pudoServices,
                    'dpd_pickup_logo' => $this->getPathUri() . 'views/img/pickup.png',
                    'dpd_locker_logo' => $this->getPathUri() . 'views/img/locker.png',
                    'delivery_address' => $deliveryAddress,
                    'saved_pudo_id' => $selectedPudo->pudo_id,
                    'is_pudo' => (bool)$isPudo,
                    'city_list' => $cityList,
                    'selected_city' => $selectedCity,
                    'show_shop_list' => Configuration::get(Config::PARCEL_SHOP_DISPLAY),
                    'street_list' => $streetList,
                    'selected_street' => $selectedStreet,
                ]
            );

            $return .= $this->context->smarty->fetch(
                'module:dpdbaltics/views/templates/hook/front/pudo-points.tpl'
            );
        }

        return $return;
    }

    /**
     * Includes Vendor Autoload.
     */
    private function autoLoad()
    {
        require_once $this->getLocalPath() . 'vendor/autoload.php';
    }

    private function compile()
    {
        $containerBuilder = new ContainerBuilder();
        $locator = new FileLocator($this->getLocalPath() . 'config');
        $loader = new YamlFileLoader($containerBuilder, $locator);
        $loader->load('config.yml');
        $containerBuilder->compile();

        $this->moduleContainer = $containerBuilder;
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        $currentController = Tools::getValue('controller');

        if (Config::isPrestashopVersionBelow174()) {
            /** @var \Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs $moduleTabs */
            $moduleTabs = $this->getService(\Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs::class);
            $visibleClasses = $moduleTabs->getTabsClassNames(false);

            if (in_array($currentController, $visibleClasses, true)) {
                Media::addJsDef(['visibleTabs' => $moduleTabs->getTabsClassNames(true)]);
                $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/tabsHandlerBelowPs174.js');
            }
        }

        if ('AdminOrders' === $currentController) {
            $this->handleLabelPrintService();

            $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin/order/order-list.css');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/order_list.js');
            Media::addJsDef(
                [
                    'dpdHookAjaxShipmentController' => $this->context->link->getAdminLink(\Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs::ADMIN_AJAX_SHIPMENTS_CONTROLLER),
                    'shipmentIsBeingPrintedMessage' => $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/partials/spinner.tpl') .
                        $this->l('Your labels are being saved please stay on the page'),
                    'noOrdersSelectedMessage' => $this->l('No orders were selected'),
                    'downloadSelectedLabelsButton' => $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/partials/download-selected-labels-button.tpl')
                ]
            );

            $orderId = Tools::getValue('id_order');
            $shipment = $this->getShipment($orderId);
            $baseUrl = $this->context->shop->getBaseURL(true, false);
            $isAbove177 = Config::isPrestashopVersionAbove177();
            $baseUrlAdmin = $isAbove177 ? $this->context->link->getAdminBaseLink() : null;
            /** @var \Invertus\dpdBaltics\Service\Label\LabelUrlFormatter $labelUrlService */
            $labelUrlService = $isAbove177 ? $this->getModuleContainer('invertus.dpdbaltics.service.label.label_url_formatter') : null;

            Media::addJsDef(
                [
                    'print_url' => $labelUrlService && $isAbove177 ? $baseUrl.$labelUrlService->formatJsLabelPrintUrl() : null,
                    'print_and_save_label_url' => $labelUrlService && $isAbove177 ? $baseUrl.$labelUrlService->formatJsLabelSaveAndPrintUrl() : null,
                    'shipment' => $shipment,
                    'id_order' => $orderId,
                    'is_label_download_option' => Configuration::get(Config::LABEL_PRINT_OPTION) === 'download',
                    'is_ps_above_177' => Config::isPrestashopVersionAbove177(),
                    'loader_url' => $isAbove177 ? "{$baseUrlAdmin}modules/dpdbaltics/views/templates/admin/loader/loader.html" : null
                ]
            );
        }

        if ('AdminOrders' === $currentController &&
            (Tools::isSubmit('vieworder') || Tools::getValue('action') === 'vieworder')
        ) {
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/order_expand_form.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/shipment.js');
            Media::addJsDef([
                'expandText' => $this->l('Expand'),
                'collapseText' => $this->l('Collapse'),
                'dpdAjaxShipmentsUrl' =>
                    $this->context->link->getAdminLink(\Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs::ADMIN_AJAX_SHIPMENTS_CONTROLLER),
                'dpdMessages' => [
                    'invalidProductQuantity' => $this->l('Invalid product quantity entered'),
                    'invalidShipment' => $this->l('Invalid shipment selected'),
                    'parcelsLimitReached' => $this->l('Parcels limit reached in shipment'),
                    'successProductMove' => $this->l('Product moved successfully'),
                    'successCreation' => $this->l('Successful creation'),
                    'unexpectedError' => $this->l('Unexpected error appeared.'),
                    'invalidPrintoutFormat' => $this->l('Invalid printout format selected.'),
                    'cannotOpenWindow' => $this->l('Cannot print label, your browser may be blocking it.'),
                    'dpdRecipientAddressError' => $this->l('Please fill required fields')
                ],
                'id_language' => $this->context->language->id,
                'id_shop' => $this->context->shop->id,
                'id_cart' => $this->context->cart->id,
                'currentController' => $currentController,

            ]);

            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/carrier_phone.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/custom_select.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/label_position.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/pudo.js');
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin/customSelect/custom-select.css');
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin/order/admin-orders-controller.css');
            $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin/order/quantity-max-value-tip.css');
        }
        if ('AdminOrders' === $currentController &&
            (Tools::isSubmit('addorder') || Tools::getValue('action') === 'addorder')
        ) {
            /** @var ProductRepository $productRepo */
            $productRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.product_repository');

            Media::addJsDef([
                'dpdFrontController' => false,
                'pudoCarriers' => json_encode($productRepo->getPudoProducts()),
                'currentController' => $currentController,
                'dpdAjaxShipmentsUrl' =>
                    $this->context->link->getAdminLink(\Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs::ADMIN_AJAX_SHIPMENTS_CONTROLLER),
                'ignoreAdminController' => true,
                'dpdAjaxPudoUrl' => $this->context->link->getAdminLink(\Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs::ADMIN_PUDO_AJAX_CONTROLLER),
                'id_shop' => $this->context->shop->id,
            ]);

            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/pudo_list.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/carrier_phone.js');
            $this->context->controller->addJS($this->getPathUri() . 'views/js/admin/pudo.js');

            $this->context->controller->addCSS($this->getPathUri() . 'views/css/admin/order/admin-orders-controller.css');

            return;
        }
    }

    public function hookDisplayAdminOrder(array $params)
    {
        return Config::isPrestashopVersionAbove177() ? false : $this->displayInAdminOrderPage($params);
    }

    public function hookDisplayAdminOrderTabContent(array $params)
    {
        return !Config::isPrestashopVersionAbove177() ? false : $this->displayInAdminOrderPage($params);
    }

    private function displayInAdminOrderPage(array $params)
    {
        $order = new Order($params['id_order']);
        $cart = Cart::getCartByOrderId($params['id_order']);

        /** @var ProductRepository $productRepo */
        $productRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.product_repository');
        $carrier = new Carrier($order->id_carrier);
        if (!$productRepo->getProductIdByCarrierReference($carrier->id_reference)) {
            return;
        }

        $shipment = $this->getShipment($order->id);
        $dpdCodWarning = false;

        /** @var OrderService $orderService */
        $orderService = $this->getModuleContainer('invertus.dpdbaltics.service.order_service');
        $orderDetails = $orderService->getOrderDetails($order, $cart->id_lang);

        $customAddresses = [];

        /** @var ReceiverAddressRepository $receiverAddressRepository */
        $receiverAddressRepository = $this->getModuleContainer('invertus.dpdbaltics.repository.receiver_address_repository');

        $customOrderAddressesIds = $receiverAddressRepository->getAddressIdByOrderId($order->id);
        foreach ($customOrderAddressesIds as $customOrderAddressId) {
            $customAddress = new Address($customOrderAddressId);

            $customAddresses[] = [
                'id_address' => $customAddress->id,
                'alias' => $customAddress->alias
            ];
        }
        $combinedCustomerAddresses = array_merge($orderDetails['customer']['addresses'], $customAddresses);

        /** @var PhonePrefixRepository $phonePrefixRepository */
        $phonePrefixRepository = $this->getModuleContainer('invertus.dpdbaltics.repository.phone_prefix_repository');

        $products = $cart->getProducts();

        /** @var LabelPositionService $labelPositionService */
        $labelPositionService = $this->getModuleContainer('invertus.dpdbaltics.service.label.label_position_service');
        $labelPositionService->assignLabelPositions($shipment->id);
        $labelPositionService->assignLabelFormat($shipment->id);

        /** @var PaymentService $paymentService */
        $paymentService = $this->getModuleContainer('invertus.dpdbaltics.service.payment.payment_service');
        try {
            $isCodPayment = $paymentService->isOrderPaymentCod($order->module);
        } catch (Exception $e) {
            $tplVars = [
                'errorMessage' => $this->l('Something went wrong checking payment method'),
            ];
            $this->context->smarty->assign($tplVars);

            return $this->context->smarty->fetch(
                $this->getLocalPath() . 'views/templates/admin/dpd-shipment-fatal-error.tpl'
            );
        }

        /** @var PudoRepository $pudoRepo */
        $pudoRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.pudo_repository');
        $selectedProduct = new DPDProduct($shipment->id_service);
        $isPudo = $selectedProduct->is_pudo;
        $pudoId = $pudoRepo->getIdByCart($order->id_cart);

        $selectedPudo = new DPDPudo($pudoId);

        /** @var ParcelShopService $parcelShopService */
        /** @var ParcelShopRepository $parcelShopRepo */
        $parcelShopService= $this->getModuleContainer('invertus.dpdbaltics.service.parcel.parcel_shop_service');
        $parcelShopRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.parcel_shop_repository');

        /** @var \Invertus\dpdBaltics\Provider\CurrentCountryProvider $currentCountryProvider */
        $currentCountryProvider = $this->getModuleContainer('invertus.dpdbaltics.provider.current_country_provider');
        $countryCode = $currentCountryProvider->getCurrentCountryIsoCode($cart);

        $selectedCity = null;
        try {
            if ($pudoId) {
                $selectedCity = $selectedPudo->city;
                $parcelShops = $parcelShopService->getParcelShopsByCountryAndCity(
                    $countryCode,
                    $selectedCity
                );
            } else {
                $parcelShops = $parcelShopService->getParcelShopsByCountryAndCity(
                    $countryCode,
                    $orderDetails['order_address']['city']
                );
            }
        } catch (Exception $e) {
            $tplVars = [
                'errorMessage' => $this->l('Fatal error while searching for parcel shops: ') . $e->getMessage(),
            ];
            $this->context->smarty->assign($tplVars);

            return $this->context->smarty->fetch(
                $this->getLocalPath() . 'views/templates/admin/dpd-shipment-fatal-error.tpl'
            );
        }

        /** @var null|\Invertus\dpdBalticsApi\Api\DTO\Object\ParcelShop $selectedPudoService */
        $selectedPudoService = null;
        $hasParcelShops = false;
        if ($parcelShops) {
            if ($selectedPudo->pudo_id) {
                $selectedPudoService = $parcelShopService->getParcelShopByShopId($selectedPudo->pudo_id)[0];
            } else {
                $selectedPudoService = $parcelShops[0];
            }
            $hasParcelShops = true;
        }

        if ($selectedPudoService) {
            /** @var PudoService $pudoService */
            $pudoService = $this->getModuleContainer('invertus.dpdbaltics.service.pudo_service');

            $selectedPudoService->setOpeningHours(
                $pudoService->formatPudoServiceWorkHours($selectedPudoService->getOpeningHours())
            );
        }

        /** @var ProductRepository $productRepository */
        $productRepository = $this->getModuleContainer('invertus.dpdbaltics.repository.product_repository');
        $dpdProducts = $productRepository->getAllProducts();

        $dpdCarrierOptions = [];

        /** @var DPDProduct $dpdProduct */
        foreach ($dpdProducts as $dpdProduct) {
            $dpdCarrierOptions[] = [
                'id_dpd_product' => $dpdProduct->id_dpd_product,
                'name' => $dpdProduct->name,
                'available' =>
                    $dpdProduct->active &&
                    (int) $dpdProduct->is_cod === (int) $isCodPayment &&
                    (!$dpdProduct->is_pudo && $hasParcelShops)
            ];
        }

        $cityList = $parcelShopRepo->getAllCitiesByCountryCode($countryCode);

        if (\Invertus\dpdBaltics\Config\Config::productHasDeliveryTime($selectedProduct->product_reference)) {
            /** @var \Invertus\dpdBaltics\Repository\OrderDeliveryTimeRepository $orderDeliveryTimeRepo */
            $orderDeliveryTimeRepo = $this->getModuleContainer()->get('invertus.dpdbaltics.repository.order_delivery_time_repository');
            $orderDeliveryTimeId = $orderDeliveryTimeRepo->getOrderDeliveryIdByCartId($cart->id);
            if ($orderDeliveryTimeId) {
                $orderDeliveryTime = new DPDOrderDeliveryTime($orderDeliveryTimeId);
                $this->context->smarty->assign([
                    'orderDeliveryTime' => $orderDeliveryTime->delivery_time,
                    'deliveryTimes' => \Invertus\dpdBaltics\Config\Config::getDeliveryTimes($countryCode)
                ]);
            }
        }

        $href = $this->context->link->getModuleLink(
            $this->name,
            'ShipmentReturn',
            [
                'id_order' => $order->id,
                'dpd-return-submit' => ''
            ]
        );

        $tplVars = [
            'dpdLogoUrl' => $this->getPathUri() . 'views/img/DPDLogo.gif',
            'shipment' => $shipment,
            'isAbove177' => Config::isPrestashopVersionAbove177(),
            'testOrder' => $shipment->is_test,
            'total_products' => 1,
            'contractPageLink' => $this->context->link->getAdminLink(\Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs::ADMIN_PRODUCTS_CONTROLLER),
            'dpdCodWarning' => $dpdCodWarning,
            'testMode' => Configuration::get(Config::SHIPMENT_TEST_MODE),
            'printLabelOption' => Configuration::get(Config::LABEL_PRINT_OPTION),
            'defaultLabelFormat' => Configuration::get(Config::DEFAULT_LABEL_FORMAT),
            'combinedAddresses' => $combinedCustomerAddresses,
            'orderDetails' => $orderDetails,
            'mobilePhoneCodeList' => $phonePrefixRepository->getCallPrefixes(),
            'products' => $products,
            'dpdProducts' => $dpdCarrierOptions,
            'isCodPayment' => $isCodPayment,
            'is_pudo' => (bool)$isPudo,
            'selectedPudo' => $selectedPudoService,
            'city_list' => $cityList,
            'selected_city' => $selectedCity,
            'has_parcel_shops' => $hasParcelShops,
            'receiverAddressCountries' => Country::getCountries($this->context->language->id, true),
            'documentReturnEnabled' => Configuration::get(Config::DOCUMENT_RETURN),
            'href' => $href,
            'adminLabelLink' => $this->context->link->getAdminLink(
                'AdminDPDBalticsAjaxShipments',
                true,
                [],
                ['action' => 'print-return']
            ),
            'isAutomated' => Configuration::get(Config::AUTOMATED_PARCEL_RETURN),
        ];

        $this->context->smarty->assign($tplVars);

        return $this->context->smarty->fetch(
            $this->getLocalPath() . 'views/templates/hook/admin/admin-order.tpl'
        );
    }

    public function hookActionValidateOrder($params)
    {
        $carrier = new Carrier($params['order']->id_carrier);
        if ($carrier->external_module_name !== $this->name) {
            return;
        }

        $isAdminOrderPage = 'AdminOrders' === Tools::getValue('controller') || Config::isPrestashopVersionAbove177();
        $isAdminNewOrderForm = Tools::isSubmit('addorder') || Tools::isSubmit('cart_summary');

        if ($isAdminOrderPage && $isAdminNewOrderForm) {
            $dpdPhone = Tools::getValue('dpd-phone');
            $dpdPhoneArea = Tools::getValue('dpd-phone-area');

            /** @var \Invertus\dpdBaltics\Service\OrderDeliveryTimeService $orderDeliveryService */
            $carrierPhoneService = $this->getModuleContainer('invertus.dpdbaltics.service.carrier_phone_service');

            if (!empty($dpdPhone) && !empty($dpdPhoneArea)) {
                try {
                    $carrierPhoneService->saveCarrierPhone(
                        $this->context->cart->id,
                        $dpdPhone,
                        $dpdPhoneArea
                    );
                } catch (Exception $exception) {
                    if ($exception->getCode() === Config::ERROR_COULD_NOT_SAVE_PHONE_NUMBER) {
                        $error = $this->l('Phone data is not saved');
                        die($error);
                    }
                }
            }

            /** @var CarrierPhoneService $carrierPhoneService */
            $orderDeliveryService = $this->getModuleContainer('invertus.dpdbaltics.service.order_delivery_time_service');
            $deliveryTime = Tools::getValue('dpd-delivery-time');
            if ($deliveryTime !== null) {
                if (!$orderDeliveryService->saveDeliveryTime(
                    $this->context->cart->id,
                    $deliveryTime
                )) {
                    $error = $this->l('Delivery time is not saved');
                    die($error);
                };
            }
        }

        /** @var ShipmentService $shipmentService */
        $shipmentService = $this->getModuleContainer('invertus.dpdbaltics.service.shipment_service');
        $shipmentService->createShipmentFromOrder($params['order']);
    }

    public function printLabel($idShipment)
    {
        /** @var \Invertus\dpdBaltics\Service\LabelPrintingService $labelPrintingService */
        $labelPrintingService = $this->getModuleContainer('invertus.dpdbaltics.service.label_printing_service');

        $parcelPrintResponse = $labelPrintingService->printOne($idShipment);

        if ($parcelPrintResponse->getStatus() === Config::API_SUCCESS_STATUS) {
            $this->updateOrderCarrier($idShipment);
            return $parcelPrintResponse;
        }

        return $parcelPrintResponse;
    }

    public function printMultipleLabels($shipmentIds)
    {
        /** @var \Invertus\dpdBaltics\Service\LabelPrintingService $labelPrintingService */
        $labelPrintingService = $this->getModuleContainer('invertus.dpdbaltics.service.label_printing_service');

        $parcelPrintResponse = $labelPrintingService->printMultiple($shipmentIds);

        if ($parcelPrintResponse->getStatus() === Config::API_SUCCESS_STATUS) {
            foreach ($shipmentIds as $shipmentId) {
                $this->updateOrderCarrier($shipmentId);
            }
            return $parcelPrintResponse;
        }

        return $parcelPrintResponse;
    }

    private function updateOrderCarrier($shipmentId)
    {
        $shipment = new DPDShipment($shipmentId);
        /** @var OrderRepository $orderRepo */
        /** @var TrackingService $trackingService */
        $orderRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.order_repository');
        $trackingService = $this->getModuleContainer('invertus.dpdbaltics.service.tracking_service');
        $orderCarrierId = $orderRepo->getOrderCarrierId($shipment->id_order);

        $orderCarrier = new OrderCarrier($orderCarrierId);
        $orderCarrier->tracking_number = $trackingService->getTrackingNumber($shipment->pl_number);

        try {
            $orderCarrier->update();
        } catch (Exception $e) {
            Context::getContext()->controller->errors[] =
                $this->l('Failed to save tracking number: ') . $e->getMessage();
            return;
        }

        $shipment->printed_label = 1;
        $shipment->date_print = date('Y-m-d H:i:s');
        $shipment->update();
    }

    public function hookDisplayOrderDetail($params)
    {
        $isReturnServiceEnabled = Configuration::get(Config::PARCEL_RETURN);
        if (!$isReturnServiceEnabled) {
            return;
        }

        if (CountryUtility::isEstonia()) {
            return;
        }

        /** @var ShipmentRepository $shipmentRepo */
        $shipmentRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.shipment_repository');
        $shipmentId = $shipmentRepo->getIdByOrderId($params['order']->id);

        $orderState = new OrderState($params['order']->current_state);
        if (!$orderState->delivery) {
            return;
        }
        /** @var AddressTemplateRepository $addressTemplateRepo */
        $addressTemplateRepo = $this->getModuleContainer('invertus.dpdbaltics.repository.address_template_repository');
        $returnAddressTemplates = $addressTemplateRepo->getReturnServiceAddressTemplates();

        $shipment = new DPDShipment($shipmentId);

        if (!$returnAddressTemplates) {
            return;
        }

        $showTemplates = false;
        if (sizeof($returnAddressTemplates) > 1 && !$shipment->return_pl_number) {
            $showTemplates = true;
        }

        if (isset($this->context->cookie->dpd_error)) {
            $this->context->controller->errors[] = json_decode($this->context->cookie->dpd_error);
            unset($this->context->cookie->dpd_error);
        }
        $href = $this->context->link->getModuleLink(
            $this->name,
            'ShipmentReturn',
            [
                'id_order' => $params['order']->id,
                'dpd-return-submit' => ''
            ]
        );

        $this->context->smarty->assign(
            [
                'href' => $href,
                'return_template_ids' => $returnAddressTemplates,
                'show_template' => $showTemplates,
            ]
        );
        $html = $this->context->smarty->fetch(
            'module:dpdbaltics/views/templates/hook/front/order-detail.tpl'
        );

        return $html;
    }

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        if ((bool) Configuration::get(Config::HIDE_ORDERS_LABEL_PRINT_BUTTON)) {
            return false;
        }

        if (isset($params['select'])) {
            $params['select'] .= ' ,ds.`id_order` AS id_order_shipment ';
        }

        if (isset($params['join'])) {
            $params['join'] .= ' LEFT JOIN `' . _DB_PREFIX_ . 'dpd_shipment` ds ON ds.`id_order` = a.`id_order` ';
        }

        $params['fields']['id_order_shipment'] = [
            'title' => $this->l('DPD Label'),
            'align' => 'text-center',
            'class' => 'fixed-width-xs',
            'orderby' => false,
            'search' => false,
            'remove_onclick' => true,
            'callback_object' => 'dpdbaltics',
            'callback' => 'returnOrderListIcon'
        ];
    }

    /**
     * Callback function, it has to be static so can't call $this, so have to reload dpdBaltics module inside the function
     * @param $idOrder
     * @return string
     * @throws Exception
     */
    public static function returnOrderListIcon($orderId)
    {
        $dpdBaltics = Module::getInstanceByName('dpdbaltics');

        $dpdBaltics->context->smarty->assign('idOrder', $orderId);

        $dpdBaltics->context->smarty->assign(
            'message',
            $dpdBaltics->l('Print label(s) from DPD system. Once label is saved you won\'t be able to modify contents of shipments')
        );
        $icon = $dpdBaltics->context->smarty->fetch(
            $dpdBaltics->getLocalPath() . 'views/templates/hook/admin/order-list-save-label-icon.tpl'
        );


        $dpdBaltics->context->smarty->assign('icon', $icon);

        return $dpdBaltics->context->smarty->fetch($dpdBaltics->getLocalPath() . 'views/templates/hook/admin/order-list-icon-container.tpl');
    }


    public function hookDisplayAdminListBefore()
    {
        if ($this->context->controller instanceof AdminOrdersControllerCore) {
            return $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/hook/admin/admin-orders-header-hook.tpl');
        }
    }

    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        if (!Config::isPrestashopVersionAbove177()) {
            return false;
        }

        $definition = $params['definition'];

        if (!(bool) Configuration::get(Config::HIDE_ORDERS_LABEL_PRINT_BUTTON)) {
            $definition->getColumns()
            ->addAfter(
                'date_add',
                (new ActionColumn('dpd_print_label'))
                    ->setName($this->l('Dpd print label'))
                    ->setOptions([
                        'actions' => $this->getGridAction()
                    ])
            );
        }

        $definition->getBulkActions()
            ->add(
                (new SubmitBulkActionCustom('print_multiple_labels'))
                    ->setName($this->l('Print multiple labels'))
                    ->setOptions([
                        'submit_route' => 'dpdbaltics_save_and_download_printed_labels_order_list_multiple',
                    ])
            )
        ;
    }

    /**
     * @return RowActionCollection
     */
    private function getGridAction()
    {
        return (new RowActionCollection())
            ->add(
                (new LinkRowActionCustom('print_delivery_slip'))
                    ->setName($this->l('Print label(s) from DPD system. Once label is saved you won\'t be able to modify contents of shipments'))
                    ->setIcon('print')
                    ->setOptions([
                        'route' => 'dpdbaltics_save_and_download_printed_label_order_list',
                        'route_param_name' => 'orderId',
                        'route_param_field' => 'id_order',
                        'is_label_download' => Configuration::get(Config::LABEL_PRINT_OPTION) === 'download',
                        'confirm_message' => $this->l('Would you like to print shipping label?'),
                        'accessibility_checker' => $this->getModuleContainer()->get('invertus.dpdbaltics.grid.row.print_accessibility_checker'),
                    ])
            );
    }

    private function getShipment($idOrder)
    {
        if (!$idOrder) {
            return false;
        }
        /** @var ShipmentRepository $shipmentRepository */
        $shipmentRepository = $this->getModuleContainer('invertus.dpdbaltics.repository.shipment_repository');
        $shipmentId = $shipmentRepository->getIdByOrderId($idOrder);
        $shipment = new DPDShipment($shipmentId);

        if (!Validate::isLoadedObject($shipment)) {
            return false;
        }

        return $shipment;
    }

    private function handleLabelPrintService()
    {
        if (Tools::isSubmit('print_label')) {
            $idShipment = Tools::getValue('id_dpd_shipment');

            try {
                $parcelPrintResponse = $this->printLabel($idShipment);
            } catch (DPDBalticsAPIException $e) {
                /** @var ExceptionService $exceptionService */
                $exceptionService = $this->getModuleContainer('invertus.dpdbaltics.service.exception.exception_service');
                Context::getContext()->controller->errors[] = $exceptionService->getErrorMessageForException(
                    $e,
                    $exceptionService->getAPIErrorMessages()
                );
                return;
            } catch (Exception $e) {
                Context::getContext()->controller->errors[] = $this->l('Failed to print label: ') . $e->getMessage();
                return;
            }

            if (isset($parcelPrintResponse) && !empty($parcelPrintResponse->getErrLog())) {
                Context::getContext()->controller->errors[] = $parcelPrintResponse->getErrLog();
            }

            exit;
        }

        if (Tools::isSubmit('print_multiple_labels')) {
            $shipmentIds = json_decode(Tools::getValue('shipment_ids'));

            try {
                $parcelPrintResponse = $this->printMultipleLabels($shipmentIds);
            } catch (DPDBalticsAPIException $e) {
                /** @var ExceptionService $exceptionService */
                $exceptionService = $this->getModuleContainer('invertus.dpdbaltics.service.exception.exception_service');
                Context::getContext()->controller->errors[] = $exceptionService->getErrorMessageForException(
                    $e,
                    $exceptionService->getAPIErrorMessages()
                );
                return;
            } catch (Exception $e) {
                Context::getContext()->controller->errors[] = $this->l('Failed to print label: ') . $e->getMessage();
                return;
            }

            if (isset($parcelPrintResponse) && !empty($parcelPrintResponse->getErrLog())) {
                Context::getContext()->controller->errors[] = $parcelPrintResponse->getErrLog();
            }

            exit;
        }
    }
}
