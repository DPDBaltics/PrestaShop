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

namespace Invertus\dpdBaltics\OnBoard\Service;

use Cache;
use Configuration;
use Cookie;
use DPDBaltics;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Repository\PriceRuleRepository;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Repository\ZoneRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OnBoardStepDataService
{
    /**
     * @var DPDBaltics
     */
    private $module;
    /**
     * @var Cookie
     */
    private $cookie;
    /**
     * @var ZoneRepository
     */
    private $zoneRepository;

    /**
     * @var PriceRuleRepository
     */
    private $priceRuleRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;

    private $importedMainFiles = array();

    public function __construct(
        DPDBaltics $module,
        Cookie $cookie,
        ZoneRepository $zoneRepository,
        PriceRuleRepository $priceRuleRepository,
        ProductRepository $productRepository
    ) {
        $this->module = $module;
        $this->cookie = $cookie;
        $this->zoneRepository = $zoneRepository;
        $this->priceRuleRepository = $priceRuleRepository;
        $this->productRepository = $productRepository;
    }

    public function getCurrentProgressBarSection()
    {
        return (int) Configuration::get(Config::ON_BOARD_MANUAL_CONFIG_CURRENT_PART);
    }


    public function checkAndAssignImportedFilesFromCookie()
    {
        if (!isset($this->cookie->{Config::ON_BOARD_COOKIE_KEY})) {
            return false;
        }

        $this->importedMainFiles = json_decode($this->cookie->{Config::ON_BOARD_COOKIE_KEY});

        return true;
    }

    public function unsetImportedFilesCookie()
    {
        unset($this->cookie->{Config::ON_BOARD_COOKIE_KEY});
    }

    public function getMainFilesImportStatus()
    {
        if (empty($this->importedMainFiles)) {
            return false;
        }

        $response = array();

        foreach (Config::getOnBoardImportTypes() as $importType) {
            $response[$importType] = array(
                'iconClass' => in_array($importType, $this->importedMainFiles) ?
                    'icon-check on-board-success-color' :
                    'icon-remove on-board-primary-color',
                'name' => $this->returnImportFileName($importType)
            );
        }

        return $response;
    }

    public function isAtLeastOneZoneCreated()
    {
        $cacheKey = 'dpdbalticsOnBoardZoneCreated';

        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        } else {
            $createdZones = $this->zoneRepository->findAllZonesIds();

            Cache::store($cacheKey, !empty($createdZones));
        }

        return Cache::retrieve($cacheKey);
    }

    public function isAtLeastOneProductActive()
    {
        $cacheKey = 'dpdBalticsOnBoardProductCreated';

        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        } else {
            $createdContracts = $this->productRepository->getAllActiveProducts();

            Cache::store($cacheKey, !empty($createdContracts));
        }

        return Cache::retrieve($cacheKey);
    }

    public function isAtLeastOnePriceRuleCreated()
    {
        $cacheKey = 'dpdBalticsOnBoardPriceRuleCreated';

        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        } else {
            $createdPriceRules = $this->priceRuleRepository->findAllPriceRuleIds();

            Cache::store($cacheKey, !empty($createdPriceRules));
        }

        return Cache::retrieve($cacheKey);
    }

    private function returnImportFileName($importOption)
    {
        switch ($importOption) {
            case Config::IMPORT_EXPORT_OPTION_ZONES:
                return $this->module->l('Zones');
            case Config::IMPORT_EXPORT_OPTION_PRICE_RULES:
                return $this->module->l('Price rules');
            case Config::IMPORT_EXPORT_OPTION_PRODUCTS:
                return $this->module->l('Products');
            default:
                return $this->module->l('Invalid export option selected');
        }
    }
}
