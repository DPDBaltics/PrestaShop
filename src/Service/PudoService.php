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


namespace Invertus\dpdBaltics\Service;

use Carrier;
use Cart;
use Configuration;
use Country;
use DPDBaltics;
use DPDProduct;
use DPDPudo;
use DPDShop;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\DTO\ShipmentData;
use Invertus\dpdBaltics\Factory\ShopFactory;
use Invertus\dpdBaltics\Provider\CurrentCountryProvider;
use Invertus\dpdBaltics\Repository\ParcelShopRepository;
use Invertus\dpdBaltics\Repository\PudoRepository;
use Invertus\dpdBaltics\Service\API\ParcelShopSearchApiService;
use Invertus\dpdBaltics\Service\Parcel\ParcelShopService;
use Invertus\dpdBalticsApi\Api\DTO\Object\OpeningHours;
use Invertus\dpdBalticsApi\Api\DTO\Object\ParcelShop;
use Invertus\ViaBill\Adapter\Context;
use Language;
use Smarty;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PudoService
{
    /** @var  array */
    public $messages = [];
    /**
     * @var PudoRepository
     */
    private $pudoRepository;
    /**
     * @var Smarty
     */
    private $smarty;
    /**
     * @var DPDBaltics
     */
    private $module;
    /**
     * @var ParcelShopSearchApiService
     */
    private $searchApiService;
    /**
     * @var GoogleApiService
     */
    private $googleApiService;
    /**
     * @var Language
     */
    private $language;
    /**
     * @var ParcelShopRepository
     */
    private $parcelShopRepository;
    /**
     * @var ShopFactory
     */
    private $shopFactory;

    public function __construct(
        PudoRepository $pudoRepository,
        Smarty $smarty,
        DPDBaltics $module,
        ParcelShopSearchApiService $searchApiService,
        Language $language,
        GoogleApiService $googleApiService,
        ParcelShopRepository $parcelShopRepository,
        ShopFactory $shopFactory
    ) {
        $this->pudoRepository = $pudoRepository;
        $this->smarty = $smarty;
        $this->module = $module;
        $this->searchApiService = $searchApiService;
        $this->googleApiService = $googleApiService;
        $this->language = $language;
        $this->parcelShopRepository = $parcelShopRepository;
        $this->shopFactory = $shopFactory;
    }

    /**
     * @param ParcelShop[] $pudoServices
     * @return array|ParcelShop[]
     */
    public function setPudoServiceTypes(array $pudoServices)
    {
        foreach ($pudoServices as &$pudoService) {
            $pudoService->type = $this->getPudoServiceType($pudoService->getParcelShopId());
        }
        return $pudoServices;
    }

    public function getPudoServiceType($pudoId)
    {
        if (Tools::substr($pudoId, 2, 2) == '90') {
            return 'locker';
        }
        return 'parcel_shop';
    }

    /**
     * @param ParcelShop[] $pudoServices
     * @return array|ParcelShop[]
     */
    public function formatPudoServicesWorkHours(array $pudoServices)
    {
        /** @var ParcelShop $pudoService */
        foreach ($pudoServices as $key => $pudoService) {
            $pudoServices[$key]->setOpeningHours($this->formatPudoServiceWorkHours($pudoService->getOpeningHours()));
        }

        return $pudoServices;
    }

    public function formatPudoServiceWorkHours(array $openingHours)
    {
        /**
         * @var  $key
         * @var OpeningHours $openingHour
         */
        foreach ($openingHours as $key => $openingHour) {
            $openingHour->setWorkHoursFormatted($this->formatWorkHours($openingHour));
            $openingHours[$key] = $openingHour;
        }

        return $openingHours;
    }

    public function formatWorkHours(OpeningHours $openingHour)
    {
        if ($openingHour->getCloseMorning() === $openingHour->getOpenAfternoon()) {
            return "{$openingHour->getOpenMorning()}-{$openingHour->getCloseAfternoon()}";
        }

        return "{$openingHour->getOpenMorning()}-{$openingHour->getCloseMorning()} {$openingHour->getOpenAfternoon()}-{$openingHour->getCloseAfternoon()}";
    }

    public function savePudoCartOrder($idCarrier, $idCart, $idPudo, $countryCode)
    {
        /** @var DPDShop $pudoShop */
        $pudoShop = DPDShop::getShopByPudoId($idPudo);
        $carrier = new Carrier($idCarrier);

        $id = $this->pudoRepository->getIdByCart($idCart);

        $pudo = new DPDPudo($id);
        $pudo->id_cart = (int)$idCart;
        $pudo->pudo_id = $idPudo;
        $pudo->id_carrier = $carrier->id;
        $pudo->country_code = $countryCode;
        $pudo->city = $pudoShop->city;
        $pudo->street = $pudoShop->street;
        $pudo->post_code = $pudoShop->p_code;

        if (!$pudo->save()) {
            return false;
        }

        return true;
    }

    public function searchPudoServices($city, $carrierId, $cartId, $countryCode = null)
    {
        if (!$countryCode) {
            $cart = null;

            if ($cartId) {
                $cart = new Cart($cartId);
            }
            /** @var CurrentCountryProvider $currentCountryProvider */
            $currentCountryProvider = $this->module->getModuleContainer('invertus.dpdbaltics.provider.current_country_provider');
            $countryCode = $currentCountryProvider->getCurrentCountryIsoCode($cart);
        }

        /** @var ParcelShopService $parcelShopService */
        $parcelShopService = $this->module->getModuleContainer('invertus.dpdbaltics.service.parcel.parcel_shop_service');

        /** @var ParcelShop[] $parcelShops */
        $parcelShops = $parcelShopService->getParcelShopsByCountryAndCity($countryCode, $city);

        $pudoServices = $this->setPudoServiceTypes($parcelShops);
        $pudoServices = $this->formatPudoServicesWorkHours($pudoServices);

        $pudoId = $this->pudoRepository->getIdByCart($cartId);
        $selectedPudo = new DPDPudo($pudoId);

        $this->smarty->assign(
            [
                'carrierId' => $carrierId,
                'pickUpMap' => Configuration::get(Config::PICKUP_MAP),
                'pudoId' => 1,
                'pudoServices' => $pudoServices,
                'dpd_pickup_logo' => $this->module->getPathUri() . 'views/img/pickup.png',
                'dpd_locker_logo' => $this->module->getPathUri() . 'views/img/locker.png',
                'countryList' => Country::getCountries($this->language->id, true),
                'selectedPudo' => $selectedPudo,
                'saved_pudo_id' => $selectedPudo->pudo_id
            ]
        );

        $coordinates = [];

        if (isset($parcelShops[0])) {
            $coordinates = [
                'lat' => $parcelShops[0]->getLatitude(),
                'lng' => $parcelShops[0]->getLongitude(),
            ];
        }

        return [
            'template' => $this->smarty->fetch(
                $this->module->getLocalPath() . '/views/templates/hook/front/partials/markers-list.tpl'
            ),
            'status' => true,
            'coordinates' => $coordinates
        ];
    }

    public function savePudoOrder($productId, $pudoId, $isoCode, $cartId, $city, $street, $zipCode)
    {
        $pudoOrderId = $this->pudoRepository->getIdByCart($cartId);
        $product = new DPDProduct($productId);
        $carrier = Carrier::getCarrierByReference($product->id_reference);
        $countryCode = $isoCode;
        $pudoOrder = new DPDPudo($pudoOrderId);
        $pudoOrder->pudo_id = $pudoId;
        $pudoOrder->id_carrier = $carrier->id;
        $pudoOrder->country_code = $countryCode;
        $pudoOrder->id_cart = $cartId;

        $pudoShop = DPDShop::getShopByPudoId($pudoId);
        if ($pudoShop && $pudoShop->id) {
            $pudoOrder->city = $pudoShop->city;
            $pudoOrder->street = $pudoShop->street;
            $pudoOrder->post_code = $pudoShop->p_code;
        } else {
            $pudoOrder->city = $city;
            $pudoOrder->street = $street;
            $pudoOrder->post_code = $zipCode;
        }

        $pudoOrder->save();
    }

    /**
     * @param ShipmentData $shipmentData
     * @param integer $idCart
     *
     * @return ShipmentData
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function repopulatePudoDataInShipment($shipmentData, $idCart)
    {
        $pudoOrderId = $this->pudoRepository->getIdByCart($idCart);
        $selectedPudo = $this->pudoRepository->getDPDPudo($pudoOrderId);

        if (!$shipmentData->getSelectedPudoId() && !empty($selectedPudo->pudo_id)) {
            $shipmentData->setSelectedPudoId($selectedPudo->pudo_id);
        } elseif (!$shipmentData->getSelectedPudoIsoCode() && !empty($selectedPudo->country_code)) {
            $shipmentData->setSelectedPudoIsoCode($selectedPudo->country_code);
        } elseif (!$shipmentData->getCity() && !empty($selectedPudo->city)) {
            $shipmentData->setCity($selectedPudo->city);
        } elseif (!$shipmentData->getDpdStreet() && !empty($selectedPudo->street)) {
            $shipmentData->setDpdStreet($selectedPudo->street);
        }

        return $shipmentData;
    }

    public function saveSelectedParcel($cartId, $city, $street, $countryCode, $idCarrier)
    {
        /** @var DPDShop $parcelShopId */
        $parcelShopId = $this->parcelShopRepository->getIdByCityAndStreet($city, $street);
        if (!$parcelShopId) {
            return true;
        }
        $parcelShop = DPDShop::getShopByPudoId($parcelShopId);
        $carrier = new Carrier($idCarrier);

        $pudoId = $this->pudoRepository->getIdByCart($cartId);

        $pudo = new DPDPudo($pudoId);

        $pudo->id_cart = (int)$cartId;
        $pudo->pudo_id = $parcelShop->parcel_shop_id;
        $pudo->id_carrier = $carrier->id;
        $pudo->country_code = $countryCode;
        $pudo->city = $parcelShop->city;
        $pudo->street = $parcelShop->street;
        $pudo->post_code = $parcelShop->p_code;

        return $pudo->save();
    }

    public function getClosestParcelShops($pudoId)
    {
        /** @var DPDShop $pudo */
        $pudo = DPDShop::getShopByPudoId($pudoId);

        if (!Validate::isLoadedObject($pudo)) {
            return [];
        }

        $parcelShops = $this->parcelShopRepository->getClosestPudoShops(
            $pudo->longitude,
            $pudo->latitude,
            Config::PARCEL_SHOP_MAP_DISTANCE,
            Config::PARCEL_SHOP_MAP_POINTS_LIMIT
        );

        if (!is_array($parcelShops)) {
            return [];
        }

        return $this->shopFactory->createShop($parcelShops);
    }

    public function getPudoIdByCityAndAddress($city, $street)
    {
        return $this->parcelShopRepository->getIdByCityAndStreet($city, $street);
    }
}
