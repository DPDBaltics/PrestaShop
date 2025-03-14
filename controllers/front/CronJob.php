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
use Invertus\dpdBaltics\Provider\ZoneRangeProvider;
use Invertus\dpdBaltics\Service\Import\API\ParcelShopImport;

if (!defined('_PS_VERSION_')) {
    exit;
}

class DpdbalticsCronJobModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        set_time_limit(0);

        $token = Tools::getValue('token');
        if ($token !== Configuration::get(Config::DPDBALTICS_HASH_TOKEN)) {
            $this->ajaxRender([
                'success' => false,
                'message' => 'wrong token'
            ]);
            exit;
        }

        $action = Tools::getValue('action');
        switch ($action) {
            case 'updateParcelShops':
                /** @var ParcelShopImport $parcelShopImport */
                $parcelShopImport = $this->module->getModuleContainer('invertus.dpdbaltics.service.import.api.parcel_shop_import');
                /** @var  ZoneRangeProvider $zoneRangeProvider */
                $zoneRangeProvider = $this->module->getModuleContainer('invertus.dpdbaltics.provider.zone_range_provider');
                $countriesInZoneRange = $zoneRangeProvider->getAllZoneRangesCountryIsoCodes();

                if ($countriesInZoneRange) {
                    foreach ($countriesInZoneRange as $country) {
                        $response = $parcelShopImport->importParcelShops($country);
                        if (isset($response['success']) && !$response['success']) {
                            $this->ajaxRender(json_encode($response));
                            exit;
                        }
                    }
                } else {
                    $countries = Country::getCountries($this->context->language->id, true);
                    foreach ($countries as $country) {
                        $response = $parcelShopImport->importParcelShops($country['iso_code']);
                        if (isset($response['success']) && !$response['success']) {
                            $this->ajaxRender(json_encode($response));
                            exit;
                        }
                    }
                }
                $this->ajaxRender(json_encode($response));
                exit;
                break;
            default:
                return;
        }
    }
}
