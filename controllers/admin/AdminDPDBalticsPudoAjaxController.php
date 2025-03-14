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

use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\Provider\CurrentCountryProvider;
use Invertus\dpdBaltics\Repository\ParcelShopRepository;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Repository\PudoRepository;
use Invertus\dpdBaltics\Service\OrderService;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsPudoAjaxController extends AbstractAdminController
{
    public function postProcess()
    {
        $action = Tools::getValue('action');
        if ($action === 'getPudoCarriers') {
            $this->ajaxProcessGetPudoCarriers();
        }

        if ($action === 'addPudoCart') {
            $this->ajaxProcessAddPudoCart();
        }
    }

    public function ajaxProcessGetPudoCarriers()
    {
        $idAddress = (int) Tools::getValue('id_address');
        $carrierId = (int) Tools::getValue('id_carrier');

        $idCart = (int) Tools::getValue('id_cart');
        $cart = new Cart($idCart);

        if (!$idAddress && !$carrierId && Validate::isLoadedObject($cart)) {
            $idAddress = $cart->id_address_delivery;
            $carrierId = $cart->id_carrier;
        }

        $address = new Address($idAddress, $this->context->language->id);
        if (!Validate::isLoadedObject($address)) {
            $this->ajaxRender('');
            exit;
        }


        /** @var ProductRepository $productRepo */
        $productRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.product_repository');
        $product = $productRepo->findProductByCarrierReference($carrierId);
        $ispudo = $product['is_pudo'];
        if (!$ispudo) {
            $this->ajaxRender('');
            exit;
        }

        $searchTemplate = $this->getPudoSearch(
            $cart
        );

        $this->ajaxRender(
            json_encode(
                array(
                    'searchTemplate' => $searchTemplate
                )
            )
        );
        exit;
    }

    public function ajaxProcessAddPudoCart()
    {
        $idCart = (int) Tools::getValue('id_cart');
        $pudoId = Tools::getValue('pudo_id');
        $idCarrier = (int) Tools::getValue('id_carrier');
        $countryCode = Tools::getValue('country_code');
        $city = Tools::getValue('city');
        $street = Tools::getValue('street');
        $postCode = Tools::getValue('post_code');

        $carrier = new Carrier($idCarrier);
        /** @var PudoRepository $pudoRepository */
        $pudoRepository = $this->module->getModuleContainer('invertus.dpdbaltics.repository.pudo_repository');
        $idPudoOrder = $pudoRepository->getPudoIdByCarrierId($carrier->id, $idCart);

        $pudoOrder = new DPDPudo($idPudoOrder);
        $pudoOrder->id_carrier = $carrier->id;
        $pudoOrder->id_cart = $idCart;
        $pudoOrder->pudo_id = $pudoId;
        $pudoOrder->country_code = $countryCode;
        $pudoOrder->city = $city;
        $pudoOrder->street = $street;
        $pudoOrder->post_code = $postCode;
        $this->ajaxRender($pudoOrder->save());
        die();
    }

    private function getPudoSearch(Cart $cart)
    {
        /** @var CurrentCountryProvider $currentCountryProvider */
        $currentCountryProvider = $this->module->getModuleContainer('invertus.dpdbaltics.provider.current_country_provider');
        /** @var ParcelShopRepository $parcelShopRepo */
        $parcelShopRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.parcel_shop_repository');

        $countryCode = $currentCountryProvider->getCurrentCountryIsoCode($cart);
        $cityList = $parcelShopRepo->getAllCitiesByCountryCode($countryCode);

        $selectedAddress = new Address($cart->id_address_delivery);

        $this->context->smarty->assign(
            [
                'receiverAddressCountries' => Country::getCountries($this->context->language->id, true),
                'city_list' => $cityList,
                'selected_city' => $selectedAddress->city
            ]
        );
        return $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/hook/admin/partials/new-order-admin.tpl'
        );
    }
}
