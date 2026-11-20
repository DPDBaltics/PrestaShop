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


namespace Invertus\dpdBaltics\Install;

use Carrier;
use Configuration;
use Country;
use Db;
use DbQuery;
use DPDBaltics;
use DPDParcel;
use DPDShipment;
use Exception;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Repository\CarrierRepository;
use Invertus\dpdBaltics\Service\Carrier\CreateCarrierService;
use Invertus\dpdBaltics\Service\Label\LabelPositionService;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Installer
{
    const FILE_NAME = 'Installer';
    
    const DB_ACTION_INSTALL = 'install';
    const DB_ACTION_UNINSTALL = 'uninstall';
    const DEFAULT_EMAIL_LANGUAGE_ISO_CODE = 'en';

    /**
     * @var DPDBaltics
     */
    private $module;


    /**
     * @var CreateCarrierService
     */
    private $createCarriersService;

    /**
     * @var CarrierRepository
     */
    private $carrierRepository;

    public function __construct(
        DPDBaltics $module,
        CreateCarrierService $createCarriersService,
        CarrierRepository $carrierRepository
    ) {
        $this->module = $module;
        $this->createCarriersService = $createCarriersService;
        $this->carrierRepository = $carrierRepository;
    }

    /**
     * @throws \Exception
     */
    public function install()
    {
        //TODO custom exceptions.
        //TODO extract messages to translator instead of translating them here.

        if (!$this->installConfiguration()) {
            throw new \Exception($this->module->l('Failed to install configuration', self::FILE_NAME));
        }

        if (!$this->processDatabase(self::DB_ACTION_INSTALL)) {
            throw new \Exception($this->module->l('Failed to install database tables', self::FILE_NAME));
        }

        if (!$this->createCarriers()) {
            throw new \Exception($this->module->l('Failed to create carriers', self::FILE_NAME));
        }

        if (!$this->registerHooks()) {
            throw new \Exception($this->module->l('Failed to register hooks', self::FILE_NAME));
        }
    }

    /**
     * @throws \Exception
     */
    public function uninstall()
    {
        //TODO custom exceptions.
        //TODO extract messages to translator instead of translating them here.

        if (!$this->uninstallConfiguration()) {
            throw new \Exception($this->module->l('Failed to uninstall configuration', self::FILE_NAME));
        }

        if (!$this->processDeleteCarriers()) {
            throw new \Exception($this->module->l('Failed to delete carriers', self::FILE_NAME));
        }

        if (!$this->processDatabase(self::DB_ACTION_UNINSTALL)) {
            throw new \Exception($this->module->l('Failed to uninstall database tables', self::FILE_NAME));
        }
    }

    /**
     * Process database install/uninstall
     *
     * @param string $action Can be "install" or "uninstall"
     *
     * @return bool
     */
    private function processDatabase($action)
    {
        $pathToSql = sprintf('sql/%s/*.sql', $action);
        $sqlFiles = glob($this->module->getLocalPath() . $pathToSql);

        foreach ($sqlFiles as $sqlFile) {
            $sqlStatements = $this->getSqlStatements($sqlFile);

            if (!$this->execute($sqlStatements)) {
                if (self::DB_ACTION_INSTALL === $action) {
                    throw new \Exception(sprintf(
                        $this->module->l('Failed to execute SQL in file %s', self::FILE_NAME),
                        $sqlFile
                    ));
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Execute SQL statements
     *
     * @param $sqlStatements
     *
     * @return bool
     */
    private function execute($sqlStatements)
    {
        try {
            $result = Db::getInstance()->execute($sqlStatements);
        } catch (Exception $e) {
            return false;
        }

        return (bool)$result;
    }

    /**
     * Format and get sql statements from file
     *
     * @param string $fileName
     *
     * @return string
     */
    private function getSqlStatements($fileName)
    {
        $sqlStatements = Tools::file_get_contents($fileName);
        $sqlStatements = str_replace('PREFIX_', _DB_PREFIX_, $sqlStatements);
        $sqlStatements = str_replace('ENGINE_TYPE', _MYSQL_ENGINE_, $sqlStatements);
        $sqlStatements = str_replace('DB_NAME', _DB_NAME_, $sqlStatements);

        return $sqlStatements;
    }

    /**
     * @return bool
     */
    public function createCarriers()
    {
        return $this->createCarriersService->createCarriers(Config::getProducts());
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            Config::SHIPMENT_TEST_MODE => 1,
            Config::WEB_SERVICE_USERNAME => '',
            Config::WEB_SERVICE_PASSWORD => '',
            Config::WEB_SERVICE_COUNTRY => $this->getDefaultWSCountry(),
            Config::SHOW_CARRIERS_IN_PRODUCT_PAGE => 0,
            Config::TRACK_LOGS => 1,
            Config::PARCEL_TRACKING => 0,
            Config::PARCEL_RETURN => 0,
            Config::DOCUMENT_RETURN => 0,
            Config::PICKUP_MAP => 0,
            Config::GOOGLE_API_KEY => '',
            Config::PARCEL_DISTRIBUTION => DPDParcel::DISTRIBUTION_NONE,
            Config::MAX_PRODUCT_WEIGHT => '',
            config::AUTO_VALUE_FOR_REF => DPDShipment::AUTO_VAL_REF_NONE,
            config::LABEL_PRINT_OPTION => config::PRINT_OPTION_DOWNLOAD,
            config::DEFAULT_LABEL_FORMAT => LabelPositionService::PDF_FORMAT_A4,
            config::DEFAULT_LABEL_POSITION => LabelPositionService::LB_POSITION_1,
            Config::ON_BOARD_INFO => '',
            Config::EXPORT_FIELD_SEPARATOR => ';',
            Config::EXPORT_FIELD_MULTIPLE_SEPARATOR => ',',
            Config::IMPORT_FIELD_MULTIPLE_SEPARATOR => ',',
            Config::EXPORT_OPTION => Config::IMPORT_EXPORT_OPTION_ZONES,
            Config::IMPORT_OPTION => Config::IMPORT_EXPORT_OPTION_ZONES,
            Config::IMPORT_LINES_SKIP => 1,
            Config::IMPORT_FIELD_SEPARATOR => ';',
            Config::IMPORT_DELETE_OLD_DATA => 0,
            Config::HIDE_ORDERS_LABEL_PRINT_BUTTON => 0,

            /** On-board */
            Config::ON_BOARD_TURNED_ON => 1,
            Config::ON_BOARD_PAUSE => 0,
            Config::ON_BOARD_STEP => Config::STEP_MAIN_1,
            Config::ON_BOARD_MANUAL_CONFIG_CURRENT_PART => 1,
            Config::DPDBALTICS_HASH_TOKEN => Tools::hash('dpdbaltics_token'),
            Config::PARCEL_SHOP_DISPLAY => Config::PARCEL_SHOP_DISPLAY_BLOCK,
        ];
    }

    private function installConfiguration()
    {
        $configuration = $this->getDefaultConfiguration();

        foreach ($configuration as $name => $value) {
            if (!Configuration::updateValue($name, $value, false, 0, 0)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Uninstall defaulf module configuration
     *
     * @return bool
     */
    private function uninstallConfiguration()
    {
        $configuration = array_keys($this->getDefaultConfiguration());

        foreach ($configuration as $name) {
            if (!Configuration::deleteByName($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function processDeleteCarriers()
    {
        $sql = 'SHOW TABLES LIKE "ps_dpd_product";';
        $carrierTable = Db::getInstance()->executeS($sql);

        if (!$carrierTable) {
            return false;
        }
        $query = new DbQuery();
        $query->select('`id_reference`');
        $query->from('dpd_product');
        $idReferences = Db::getInstance()->executeS($query);

        if (empty($idReferences)) {
            return false;
        }
        foreach ($idReferences as $id) {
            $carrier = Carrier::getCarrierByReference($id['id_reference']);
            if (Validate::isLoadedObject($carrier)) {
                $carrier->deleted = 1;
                $carrier->update();
            }
        }

        return true;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function deleteModuleCarriers()
    {
        $dpdCarriers = $this->carrierRepository->getDpdModuleCarrierReferences();

        foreach ($dpdCarriers as $reference) {

            $carrier = Carrier::getCarrierByReference($reference);
            if (Validate::isLoadedObject($carrier)) {
                $carrier->deleted = 1;
                $carrier->update();
            }
        }

        return true;
    }

    private function getDefaultWSCountry()
    {
        $defaultCountryId = Configuration::get('PS_COUNTRY_DEFAULT');
        $defaultCountry = new Country($defaultCountryId);

        switch ($defaultCountry->iso_code) {
            case Config::ESTONIA_ISO_CODE:
                $wsCountry = Config::ESTONIA_ISO_CODE;
                break;
            case Config::LITHUANIA_ISO_CODE:
                $wsCountry = Config::LITHUANIA_ISO_CODE;
                break;
            default:
                $wsCountry = Config::LATVIA_ISO_CODE;
        }

        return $wsCountry;
    }

    private function registerHooks()
    {
        $hooks = $this->hooks();

        if (empty($hooks)) {
            return true;
        }

        return $this->module->registerHook($hooks);
    }

    /**
     * List of hooks to register
     *
     * @return array|string[]
     */
    private function hooks()
    {
        return [
            'displayCarrierExtraContent',
            'actionFrontControllerSetMedia',
            'displayProductPriceBlock',
            'actionValidateOrder',
            'displayAdminOrder',
            'displayAdminOrderTabContent',
            'actionAdminControllerSetMedia',
            'actionValidateStepComplete',
            'displayOrderDetail',
            'displayBackOfficeTop',
            'displayOrderDetail',
            'actionAdminOrdersListingFieldsModifier',
            'displayAdminListBefore',
            'displayAdminOrderTabContent',
            'actionOrderGridDefinitionModifier',
        ];
    }
}
