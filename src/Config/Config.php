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


namespace Invertus\dpdBaltics\Config;

use Configuration;
use DPDParcel;
use DPDShipment;
use Invertus\dpdBaltics\Collection\DPDProductInstallCollection;
use Invertus\dpdBaltics\DTO\DPDProductInstall;
use Module;

if (!defined('_PS_VERSION_')) {
    exit;
}


class Config
{

    /** settings tab configuration **/
    const SHIPMENT_TEST_MODE = 'DPD_SHIPMENT_TEST_MODE';
    const WEB_SERVICE_USERNAME = 'DPD_WEB_SERVICE_USERNAME';
    const WEB_SERVICE_PASSWORD = 'DPD_WEB_SERVICE_PASSWORD';
    const WEB_SERVICE_COUNTRY = 'DPD_WEB_SERVICE_COUNTRY';
    const SHOW_CARRIERS_IN_PRODUCT_PAGE = 'DPD_SHOW_CARRIERS_IN_PRODUCT_PAGE';
    const TRACK_LOGS = 'DPD_TRACK_LOGS';

    const LITHUANIA_ISO_CODE = 'LT';
    const LATVIA_ISO_CODE = 'LV';
    const ESTONIA_ISO_CODE = 'EE';
    const PORTUGAL_ISO_CODE = 'PT';
    const VALID_TRACKING_URL_COUNTRIES = ['LT', 'EE', 'LV'];

    const AVAILABLE_PUDO_COD_IDS = [
        'EE90',
        'EE10',
        'LT90',
        'LV90',
        'LV10'
    ];

    /** In seconds. Used as minimum execution time for some of the scripts.  */
    const SCRIPT_EXECUTION_TIME = 30;

    const COLOR_INFO = '#0275d8';
    const COLOR_SUCCESS = '#72C279';
    const COLOR_WARNING = '#fbbb22';
    const COLOR_LIMITED_ZONES = '#0075d3';
    const COLOR_ALL_ZONES = '#67c07c';
    const COLOR_ONE_ZONE = '#ededed';

    const GOOGLE_MAPS_API_KEY_LINK = 'https://developers.google.com/maps/documentation/javascript/get-api-key';

    const CARRIER_TYPE_CLASSIC = 'DPD_CARRIER_TYPE_CLASSIC';

    const IMAGE_SIZE_40_X_40 = '40';
    const IMAGE_SIZE_50_X_50 = '50';

    /** default image extension for carriers image */
    const CARRIER_LOGO_EXTENSION = 'png';

    /**
     * default directories based on carrier type . Located in views/img/carriers folder
     *
     */
    const CARRIER_PICKUP_DIR_NAME = 'pudo';
    const CARRIER_DEFAULT_DIR_NAME = 'default';

    /** Import / Export configuration */
    const EXPORT_FIELD_SEPARATOR = 'DPD_EXPORT_FIELD_SEPARATOR';
    const EXPORT_FIELD_MULTIPLE_SEPARATOR = 'DPD_MULTIPLE_EXPORT_FIELD_SEP';
    const EXPORT_OPTION = 'DPD_EXPORT_OPTION';
    const IMPORT_OPTION = 'DPD_IMPORT_OPTION';
    const IMPORT_LINES_SKIP = 'DPD_IMPORT_LINES_SKIP';
    const IMPORT_DELETE_OLD_DATA = 'DPD_IMPORT_DELETE_OLD_DATA';
    const IMPORT_FIELD_SEPARATOR = 'DPD_IMPORT_FIELD_SEPARATOR';
    const IMPORT_FIELD_MULTIPLE_SEPARATOR = 'DPD_MULTIPLE_IMPORT_FIELD_SEP';
    const IMPORT_INFO_BLOCK_FIELD = 'DPD_IMPORT_INFO_BLOCK_FIELD';
    const IMPORT_ZONE_INFO_BLOCK_FIELD = 'DPD_ZONE_IMPORT_INFO_BLOCK_FIELD';
    const IMPORT_PARCEL_INFO_BLOCK_FIELD = 'DPD_PARCEL_IMPORT_INFO_BLOCK_FIELD';
    const DPD_PARCEL_IMPORT_COUNTRY_SELECTOR = 'DPD_PARCEL_IMPORT_COUNTRY_SELECTOR';
    const IMPORT_FILE = 'DPD_IMPORT_FILE';
    const PRESTASHOP_DPD_CARRIER_REGENERATE = 'PRESTASHOP_DPD_CARRIER_REGENERATE';

    /**
     * Please note those are also used as file names for exports, so they must be without spaces
     */
    const IMPORT_EXPORT_OPTION_ADDRESS_TEMPLATES = 'address_templates';
    const IMPORT_EXPORT_OPTION_ZONES = 'zones';
    const IMPORT_EXPORT_OPTION_SETTINGS = 'settings';
    const IMPORT_EXPORT_OPTION_PRODUCTS = 'products';
    const IMPORT_EXPORT_OPTION_PRICE_RULES = 'price_rules';
    const IMPORT_EXPORT_OPTION_SHIPMENT_LIST_SETTINGS = 'shipment_list_settings';
    const IMPORT_EXPORT_OPTION_RETURN_LIST_SETTINGS = 'return_list_settings';
    const IMPORT_EXPORT_OPTION_ADDRESS_LIST_SETTINGS = 'address_list_settings';
    const IMPORT_EXPORT_OPTION_ALL_ZIP = 'all_zip';

    /** General configuration */
    const LABEL_PRINT_OPTION = 'DPD_LABEL_PRINT_OPTION';
    const DEFAULT_LABEL_FORMAT = 'DPD_DEFAULT_LABEL_FORMAT';
    const DEFAULT_LABEL_POSITION = 'DPD_DEFAULT_LABEL_POSITION';
    const SEND_EMAIL_ON_PARCEL_CREATION = 'DPD_SEND_EMAIL_TO_CUSTOMER';
    const MULTIPLE_LABEL_NAME_MAX_SIZE = 44;
    const HIDE_ORDERS_LABEL_PRINT_BUTTON = 'DPD_HIDE_ORDERS_LABEL_PRINT_BUTTON';

    const PHONE_CODE_PREFIX = '+';

    /** COD Payment configuration */
    const COD_PAYMENT_SWAP = 'DPD_COD_PAYMENT_SWAP';

    const IS_CANCEL_BUTTON_DEFAULT = false;
    const PARCEL_DISTRIBUTION = 'DPD_PARCEL_DISTRIBUTION';

    const PARCEL_TRACKING = 'DPD_PARCEL_TRACKING';
    const SECRET_TEST_MODE = 'DPD_SECRET_TEST_MODE';
    const PARCEL_TRACKING_TOKEN = 'DPD_PARCEL_TRACKING_TOKEN';
    const PARCEL_RETURN = 'DPD_PARCEL_RETURN';
    const PICKUP_MAP = 'DPD_PICKUP_MAP';
    const DOCUMENT_RETURN = 'DPD_DOCUMENT_RETURN';
    const PARCEL_SHOP_DISPLAY = 'PARCEL_SHOP_DISPLAY';
    const AUTOMATED_PARCEL_RETURN = 'AUTOMATED_PARCEL_RETURN';
    const GOOGLE_API_KEY = 'DPD_GOOGLE_API_KEY';
    const SHIPMENTS_LIST_CONFIGURATION = 'DPD_SHIPMENTS_LIST_CONFIGURATION';
    const SHIPMENTS_RETURN_LIST_CONFIGURATION = 'DPD_SHIPMENTS_RETURN_LIST_CONFIGURATION';
    const ADDRESS_LIST_CONFIGURATION = 'DPD_ADDRESS_LIST_CONFIGURATION';
    const AUTO_VALUE_FOR_REF = 'DPD_AUTO_VALUE_FOR_REF';
    const DPD_SHIPMENT_RETURN_CONFIRMATION = 'dpd_shipment_return_confirmation';
    const PRINT_OPTION_DOWNLOAD = 'download';
    const PRINT_OPTION_BROWSER = 'browser_print';
    const API_SUCCESS_STATUS = 'ok';
    const API_COLLECTION_REQUEST_SUCCESS_STATUS = '200 1. row import was successful!';
    const API_COURIER_REQUEST_SUCCESS_STATUS = '<p>DONE';
    const API_COLLECTION_REQUEST_ERROR_STATUS = '402 Error:';
    const API_COURIER_REQUEST_ERROR_STATUS = '402 Error:';
    const API_RESPONSE_ERROR_STATUS = 'err';
    const RETURN_TEMPLATE_DEFAULT_ID = 1;

    const DEFAULT_LABEL_TYPE = 'pdf';

    const ZONES_SAVE_AND_STAY_BUTTON = 'processSaveAndStayZoneRanges';

    const FETCH_PUDO_POINT = 1;
    const RETRIEVE_OPENING_HOURS = 1;

    const MAXIMUM_PUDO_POINTS_IN_MAP = 30;

    /** on board configuration */
    const ON_BOARD_INFO = 'DPD_ON_BOARD_INFO';
    const ON_BOARD_TURNED_ON = 'DPD_ON_BOARD_TURNED_ON';
    const ON_BOARD_PAUSE = 'DPD_ON_BOARD_PAUSE';
    const ON_BOARD_STEP = 'DPD_ON_BOARD_STEP';
    const ON_BOARD_COOKIE_KEY = 'DPD_ON_BOARD_COOKIE';

    /** DPD on-board steps  */
    const STEP_MAIN_1 = 'StepMain1';
    const STEP_MAIN_2 = 'StepMain2';
    const STEP_MAIN_3 = 'StepMain3';

    const STEP_MANUAL_ZONES_0 = 'StepManualZones0';
    const STEP_MANUAL_ZONES_1 = 'StepManualZones1';
    const STEP_MANUAL_ZONES_2 = 'StepManualZones2';
    const STEP_MANUAL_ZONES_3 = 'StepManualZones3';
    const STEP_MANUAL_ZONES_4 = 'StepManualZones4';
    const STEP_MANUAL_ZONES_5 = 'StepManualZones5';
    const STEP_MANUAL_ZONES_6 = 'StepManualZones6';
    const STEP_MANUAL_ZONES_AFTER_IMPORT = 'StepManualZonesAfterImport';

    const STEP_MANUAL_PRODUCTS_0 = 'StepManualProducts0';
    const STEP_MANUAL_PRODUCTS_1 = 'StepManualProducts1';
    const STEP_MANUAL_PRODUCTS_2 = 'StepManualProducts2';
    const STEP_MANUAL_PRODUCTS_3 = 'StepManualProducts3';
    const STEP_MANUAL_PRODUCTS_4 = 'StepManualProducts4';
    const STEP_MANUAL_PRODUCTS_5 = 'StepManualProducts5';
    const STEP_MANUAL_PRODUCTS_5_SHOP = 'StepManualProducts5Shop';
    const STEP_MANUAL_PRODUCTS_6 = 'StepManualProducts6';
    const STEP_MANUAL_PRODUCTS_7 = 'StepManualProducts7';
    const STEP_MANUAL_PRODUCTS_8 = 'StepManualProducts8';
    const STEP_MANUAL_PRODUCTS_9 = 'StepManualProducts9';

    const STEP_MANUAL_PRICE_RULES_0 = 'StepManualPriceRules0';
    const STEP_MANUAL_PRICE_RULES_1 = 'StepManualPriceRules1';
    const STEP_MANUAL_PRICE_RULES_2 = 'StepManualPriceRules2';
    const STEP_MANUAL_PRICE_RULES_3 = 'StepManualPriceRules3';
    const STEP_MANUAL_PRICE_RULES_4 = 'StepManualPriceRules4';
    const STEP_MANUAL_PRICE_RULES_5 = 'StepManualPriceRules5';
    const STEP_MANUAL_PRICE_RULES_6 = 'StepManualPriceRules6';
    const STEP_MANUAL_PRICE_RULES_7 = 'StepManualPriceRules7';
    const STEP_MANUAL_PRICE_RULES_8 = 'StepManualPriceRules8';

    const STEP_IMPORT_1 = 'StepImport1';
    const STEP_IMPORT_2 = 'StepImport2';
    const STEP_IMPORT_FINISH = 'StepImportFinish';
    const STEP_MANUAL_CONFIG_FINISH = 'StepManualConfigFinish';

    const ON_BOARD_ZONES_PART = 1;
    const ON_BOARD_PRODUCTS_PART = 2;
    const ON_BOARD_PRICE_RULES_PART = 3;

    const ON_BOARD_MANUAL_CONFIG_CURRENT_PART = 'DPD_ON_BOARD_MANUAL_CONFIG_CURRENT_PART';

    const DPDBALTICS_HASH_TOKEN = 'DPD_HASH_TOKEN';

    const ON_BOARD_PROGRESS_BAR_SECTIONS = 3;

    const ON_BOARD_PROGRESS_BAR_ZONES_STEPS = 5;
    const ON_BOARD_PROGRESS_BAR_PRODUCTS_STEPS = 8;
    const ON_BOARD_PROGRESS_PRICE_RULES_STEPS = 8;

    const ON_BOARD_PROGRESS_STEP_1 = 1;
    const ON_BOARD_PROGRESS_STEP_2 = 2;
    const ON_BOARD_PROGRESS_STEP_3 = 3;
    const ON_BOARD_PROGRESS_STEP_4 = 4;
    const ON_BOARD_PROGRESS_STEP_5 = 5;
    const ON_BOARD_PROGRESS_STEP_6 = 6;
    const ON_BOARD_PROGRESS_STEP_7 = 7;
    const ON_BOARD_PROGRESS_STEP_8 = 8;

    const STEP_FAST_MOVE_FORWARD = 'forward';
    const STEP_FAST_MOVE_BACKWARD = 'backward';

    const MULTI_LANG_FIELD_SEPARATOR = ':';
    const WEB_SERVICE_PASSWORD_PLACEHOLDER = '&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;';

    const PARCEL_SHOP_DISPLAY_LIST = 'LIST';
    const PARCEL_SHOP_DISPLAY_BLOCK = 'BLOCK';
    const PARCEL_SHOP_MAP_DISTANCE = 50;
    const PARCEL_SHOP_MAP_POINTS_LIMIT = 25;

    const COURIER_SAME_DAY_TIME_LIMITATION = '15:00';
    const COURIER_SAME_DAY_TIME_ADDITIONAL_MINUTES = '30';

    const SAME_DAY_DELIVERY_CITY =  'Rīga';
    const DOCUMENT_RETURN_CODE = '-DOCRET';

    const PRODUCT_TYPE_B2B = 'D-B2C';
    const PRODUCT_TYPE_PUDO = 'PS';
    const PRODUCT_TYPE_B2B_COD = 'D-B2C-COD';
    const PRODUCT_TYPE_PUDO_COD = 'PS-COD';
    const PRODUCT_TYPE_SATURDAY_DELIVERY = 'D-B2C-SAT';
    const PRODUCT_TYPE_SATURDAY_DELIVERY_COD = 'D-B2C-COD-SAT';
    const PRODUCT_TYPE_SAME_DAY_DELIVERY = '274';
    const PS_VERSION_1_7_7 = '1.7.7.0';

    const COUNTRY_ISO_CODES_WITH_MIXED_CHARACTERS = ['IE', 'GB', 'NL'];

    // NOTE: Add OPC module tech name if payment option is compatible
    public const DPD_OPC_MODULE_LIST = ['onepagecheckoutps', 'supercheckout', 'thecheckout'];

    const PRODUCT_NAME_B2B = [
        'LT' => 'Pristatymas privatiems asmenims',
        'EE' => 'DPD kuller',
        'LV' => 'Kurjerpiegādes Privātpersonām',
        'EN' => 'DPD courier',
    ];

    const PRODUCT_NAME_PUDO = [
        'LT' => 'Pristatymas į paštomatą',
        'EE' => 'DPD pakiautomaat',
        'LV' => 'Piegādes uz Paku Skapjiem un Paku Bodēm',
        'EN' => 'DPD Pickup points',
    ];

    const PRODUCT_NAME_B2B_COD = [
        'LT' => 'Atsiskaitymas pristatymo metu',
        'EE' => 'DPD kuller lunamaksega',
        'LV' => 'Naudas iekasēšana piegādes brīdī',
        'EN' => 'Cash on Delivery',
    ];


    const PRODUCT_NAME_PUDO_COD = [
        'LT' => 'DPD Paštomatas, atsiskaitymas grynaisiais',
        'EE' => 'DPD pakiautomaat lunamaksega',
        'LV' => 'Piegādes uz Paku Skapjiem un Paku Bodēm, COD',
        'EN' => 'DPD Pickup COD',
    ];

    const PRODUCT_NAME_SATURDAY_DELIVERY = [
        'LT' => 'Pristatymas šeštadienį',
        'EE' => 'DPD kuller laupäeval',
        'LV' => 'Sestdienas piegāde',
        'EN' => 'DPD Saturday',
    ];

    const PRODUCT_NAME_SATURDAY_DELIVERY_COD = [
        'LT' => 'Pristatymas Šestadienį, atsiskaitymas grynaisiais',
        'EE' => 'DPD kuller laupäeval lunamaksega',
        'LV' => 'Piegāde sestdienā, COD',
        'EN' => 'Saturday Delivery COD',
    ];

    const PRODUCT_NAME_SAME_DAY_DELIVERY = [
        'LT' => 'Pristatymas tą pačią dieną',
        'EE' => 'Samal päeval kohaletoimetamine',
        'LV' => 'Same day Delivery',
        'EN' => 'Same day Delivery',
    ];

    const ERROR_COULD_NOT_SAVE_PHONE_NUMBER = 501;
    const ERROR_BAD_PHONE_NUMBER_PREFIX = 502;
    const ERROR_EMPTY_PHONE_DETAILS = 509;
    const ERROR_PHONE_EMPTY = 505;
    const ERROR_PHONE_HAS_INVALID_CHARACTERS = 506;
    const ERROR_PHONE_HAS_INVALID_LENGTH = 507;
    const ERROR_INVALID_PUDO_TERMINAL = 508;
    const ERROR_COULD_NOT_LOAD_CART = 509;

    public static function getProducts($webServiceCountry = 'EN')
    {
        $collection = new DPDProductInstallCollection();

        $product = self::getProductByReference(self::PRODUCT_TYPE_B2B, $webServiceCountry);
        $collection->add($product);

        $product = self::getProductByReference(self::PRODUCT_TYPE_PUDO, $webServiceCountry);
        $collection->add($product);

        $product = self::getProductByReference(self::PRODUCT_TYPE_B2B_COD, $webServiceCountry);
        $collection->add($product);

        $product = self::getProductByReference(
            self::PRODUCT_TYPE_SATURDAY_DELIVERY,
            $webServiceCountry
        );
        $collection->add($product);

        $product = self::getProductByReference(
            self::PRODUCT_TYPE_PUDO_COD,
            $webServiceCountry
        );
        $collection->add($product);

        $product = self::getProductByReference(
            self::PRODUCT_TYPE_SATURDAY_DELIVERY_COD,
            $webServiceCountry
        );
        $collection->add($product);

        return $collection;
    }

    public static function getCarrierLogo($idCarrier)
    {
        return _THEME_SHIP_DIR_ . $idCarrier . '.jpg';
    }

    public static function getPsAndModuleVersion()
    {
        $module = Module::getInstanceByName('dpdbaltics');

        return 'PS_'._PS_VERSION_.'|'. $module->version;
    }


    /**
     * Get default module configuration.
     * Configuration is installed upon module's installation
     * and same configuration is deleted on uninstallation.
     *
     * @return array
     */
    public static function getDefaultConfiguration()
    {
        return [
            self::EXPORT_FIELD_SEPARATOR => ';',
            self::EXPORT_FIELD_MULTIPLE_SEPARATOR => ',',
            self::EXPORT_OPTION => self::IMPORT_EXPORT_OPTION_ZONES,
            self::IMPORT_OPTION => self::IMPORT_EXPORT_OPTION_ZONES,
            self::IMPORT_FIELD_MULTIPLE_SEPARATOR => ',',
            self::IMPORT_FIELD_SEPARATOR => ';',
            self::IMPORT_LINES_SKIP => 1,
            self::PARCEL_DISTRIBUTION => DPDParcel::DISTRIBUTION_NONE,
            self::LABEL_PRINT_OPTION => 'download',
            self::AUTO_VALUE_FOR_REF => DPDShipment::AUTO_VAL_REF_NONE,
        ];
    }

    public static function getUsername()
    {
        return Configuration::get(self::WEB_SERVICE_USERNAME);
    }

    public static function getPassword()
    {
        return str_rot13(Configuration::get(self::WEB_SERVICE_PASSWORD));
    }

    public static function getOnBoardImportTypes()
    {
        return [
            Config::IMPORT_EXPORT_OPTION_ZONES,
            Config::IMPORT_EXPORT_OPTION_PRODUCTS,
            Config::IMPORT_EXPORT_OPTION_PRICE_RULES,
        ];
    }

    public static function getProductByReference($productReference, $countryCode = 'EN')
    {
        switch ($productReference) {
            case self::PRODUCT_TYPE_B2B:
                $product = new DPDProductInstall();
                $product->setName(self::PRODUCT_NAME_B2B[$countryCode]);
                $product->setId(self::PRODUCT_TYPE_B2B);
                $product->setDelay('Your delivery experts');
                $product->setIsPudo(0);
                $product->setIsCod(0);
                return $product;
                break;
            case self::PRODUCT_TYPE_PUDO:
                $product = new DPDProductInstall();
                $product->setName(self::PRODUCT_NAME_PUDO[$countryCode]);
                $product->setId(self::PRODUCT_TYPE_PUDO);
                $product->setDelay('Your delivery experts');
                $product->setIsPudo(1);
                $product->setIsCod(0);
                return $product;
                break;
            case self::PRODUCT_TYPE_B2B_COD:
                $product = new DPDProductInstall();
                $product->setName(self::PRODUCT_NAME_B2B_COD[$countryCode]);
                $product->setId(self::PRODUCT_TYPE_B2B_COD);
                $product->setDelay('Your delivery experts');
                $product->setIsPudo(0);
                $product->setIsCod(1);
                return $product;
                break;
            case self::PRODUCT_TYPE_PUDO_COD:
                $product = new DPDProductInstall();
                $product->setName(self::PRODUCT_NAME_PUDO_COD[$countryCode]);
                $product->setId(Config::PRODUCT_TYPE_PUDO_COD);
                $product->setDelay('Your delivery experts');
                $product->setIsPudo(1);
                $product->setIsCod(1);
                return $product;
                break;
            case self::PRODUCT_TYPE_SATURDAY_DELIVERY:
                $product = new DPDProductInstall();
                $product->setName(self::PRODUCT_NAME_SATURDAY_DELIVERY[$countryCode]);
                $product->setId(self::PRODUCT_TYPE_SATURDAY_DELIVERY);
                $product->setDelay('Your delivery experts');
                $product->setIsPudo(0);
                $product->setIsCod(0);
                return $product;
                break;
            case self::PRODUCT_TYPE_SATURDAY_DELIVERY_COD:
                $product = new DPDProductInstall();
                $product->setName(self::PRODUCT_NAME_SATURDAY_DELIVERY_COD[$countryCode]);
                $product->setId(self::PRODUCT_TYPE_SATURDAY_DELIVERY_COD);
                $product->setDelay('Your delivery experts');
                $product->setIsPudo(0);
                $product->setIsCod(1);
                return $product;
                break;
            case self::PRODUCT_TYPE_SAME_DAY_DELIVERY:
                $product = new DPDProductInstall();
                $product->setName(self::PRODUCT_NAME_SAME_DAY_DELIVERY[$countryCode]);
                $product->setId(self::PRODUCT_TYPE_SAME_DAY_DELIVERY);
                $product->setDelay('Your delivery experts');
                $product->setIsPudo(1);
                $product->setIsCod(0);
                return $product;
                break;
            default:
                return false;
                break;
        }
    }

    public static function getDefaultServiceWeights($countryIso, $productReference)
    {
        switch ($countryIso) {
            case self::ESTONIA_ISO_CODE:
                switch ($productReference) {
                    case self::PRODUCT_TYPE_PUDO:
                    case self::PRODUCT_TYPE_PUDO_COD:
                        return 31.5;
                    case self::PRODUCT_TYPE_SATURDAY_DELIVERY:
                    case self::PRODUCT_TYPE_SAME_DAY_DELIVERY:
                    case self::PRODUCT_TYPE_SATURDAY_DELIVERY_COD:
                        return 31.5;
                    default:
                        return false;
                }
            case self::LITHUANIA_ISO_CODE:
            case self::LATVIA_ISO_CODE:
                switch ($productReference) {
                    case self::PRODUCT_TYPE_PUDO:
                    case self::PRODUCT_TYPE_PUDO_COD:
                        return 31.5;
                    case self::PRODUCT_TYPE_SAME_DAY_DELIVERY:
                        return 31.5;
                    case self::PRODUCT_TYPE_SATURDAY_DELIVERY_COD:
                    case self::PRODUCT_TYPE_SATURDAY_DELIVERY:
                    default:
                        return false;
                }
            case self::PORTUGAL_ISO_CODE:
                switch ($productReference) {
                    case self::PRODUCT_TYPE_PUDO:
                    case self::PRODUCT_TYPE_PUDO_COD:
                        return 10.0;
                    case self::PRODUCT_TYPE_SATURDAY_DELIVERY:
                    case self::PRODUCT_TYPE_SAME_DAY_DELIVERY:
                    case self::PRODUCT_TYPE_SATURDAY_DELIVERY_COD:
                        return 31.5;
                    default:
                        return false;
                }
            default:
                switch ($productReference) {
                    case self::PRODUCT_TYPE_PUDO:
                    case self::PRODUCT_TYPE_PUDO_COD:
                        return 20.0;
                    case self::PRODUCT_TYPE_SATURDAY_DELIVERY:
                    case self::PRODUCT_TYPE_SAME_DAY_DELIVERY:
                    case self::PRODUCT_TYPE_SATURDAY_DELIVERY_COD:
                        return 31.5;
                    default:
                        return false;
                }
        }
    }

    public static function getTimeFrameCountries($countryIso)
    {
        switch ($countryIso) {
            case self::LITHUANIA_ISO_CODE:
                return [
                    'vilnius',
                    'kaunas',
                    'klaipėda',
                    'klaipeda',
                    'šiauliai',
                    'siauliai',
                    'panevėžys',
                    'panevezys',
                    'alytus',
                    'marijampolė',
                    'marijampole',
                    'telšiai',
                    'telsiai',
                    'tauragė',
                    'taurage',
                    'utena',
                ];
            case self::LATVIA_ISO_CODE:
                return [
                    'riga',
                    'rīga',
                    'talsi',
                    'talsi',
                    'liepaja',
                    'liepāja',
                    'jelgava',
                    'jelgava',
                    'jekabpils',
                    'jēkabpils',
                    'daugavpils',
                    'rezekne',
                    'rēzekne',
                    'valmiera',
                    'gulbene',
                    'cesis',
                    'cēsis',
                    'saldus',
                    'saldus',
                    'ventspils',
                    'ventspils',
                ];
            default:
                return [];
        }
    }

    public static function getDeliveryTimes($isoCode)
    {
        switch ($isoCode) {
            case Config::LITHUANIA_ISO_CODE:
                return [
                    '08:00-18:00' => '08:00-18:00',
                    '08:00-14:00' => '08:00-14:00',
                    '14:00-18:00' => '14:00-18:00',
                    '18:00-22:00' => '18:00-22:00',
                ];
            case Config::LATVIA_ISO_CODE:
                return [
                    '08:00-18:00' => '08:00-18:00',
                    '18:00-22:00' => '18:00-22:00',
                ];

        }
    }

    public static function productHasDeliveryTime($productReference)
    {
        switch ($productReference) {
            case self::PRODUCT_TYPE_B2B:
            case self::PRODUCT_TYPE_B2B_COD:
                return true;
            default:
                return false;
        }
    }

    public static function getMinimalTimeIntervalForCountry($countryIso)
    {
        switch ($countryIso) {
            case self::LITHUANIA_ISO_CODE:
                return 150;
            default:
                return 120;
        }
    }

    /**
     * @return bool
     * Checks is prestashop version is higher than 1.7.7
     */
    public static function isPrestashopVersionAbove177()
    {
        if (_PS_VERSION_ >= self::PS_VERSION_1_7_7) {

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function isPrestashopVersionBelow174()
    {
        return (bool) version_compare(_PS_VERSION_, '1.7.4', '<');
    }
}
