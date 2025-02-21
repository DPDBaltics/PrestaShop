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
use Invertus\dpdBaltics\Service\API\ParcelShopSearchApiService;
use Invertus\dpdBaltics\Service\Import\API\ParcelShopImport;
use Invertus\dpdBaltics\Service\Import\ImportMainZone;
use Invertus\dpdBaltics\Service\Parcel\ParcelUpdateService;
use Invertus\dpdBaltics\Service\PudoService;
use Invertus\dpdBalticsApi\Api\DTO\Response\ParcelShopSearchResponse;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsAjaxController extends AbstractAdminController
{
    public function ajaxProcessImportZones()
    {
        /** @var ImportMainZone $importOnLoginService */
        $importOnLoginService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.import.import_main_zone');

        $selectedCountry = Tools::getValue('country');
        switch ($selectedCountry) {
            case 'latvia' :
                $this->ajaxRender(json_encode($importOnLoginService->importLatviaZones()));
                die();
                break;
            case 'lithuania':
                $this->ajaxRender(json_encode($importOnLoginService->importLithuaniaZones()));
                die();
                break;
            default:
                $this->ajaxRender();
                die();
                break;
        }
    }

    public function ajaxProcessImportParcels()
    {
        /** @var ParcelShopImport $parcelShopImport */
        $parcelShopImport = $this->module->getModuleContainer('invertus.dpdbaltics.service.import.api.parcel_shop_import');

        $countryId = Tools::getValue('countryId');
        $countryIso = Country::getIsoById($countryId);
        $this->ajaxRender(json_encode($parcelShopImport->importParcelShops($countryIso)));
        die();
    }
}
