<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /LICENSE
 *
 * International Registered Trademark & Property of INVERTUS, UAB
 */
namespace Invertus\dpdBaltics\Infrastructure\Bootstrap;

use Invertus\dpdBaltics\Infrastructure\Adapter\ModuleFactory;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ModuleTabs
{
    const FILE_NAME = 'TabService';

    /**
     * Controller for displaying dpd links in prestashop's menu
     */
    const ADMIN_DPDBALTICS_MODULE_CONTROLLER = 'AdminDPDBalticsModule';

    const ADMIN_ZONES_CONTROLLER = 'AdminDPDBalticsZones';
    const ADMIN_PRODUCT_AVAILABILITY_CONTROLLER = 'AdminDPDBalticsProductsAvailability';
    const ADMIN_PRODUCTS_CONTROLLER = 'AdminDPDBalticsProducts';
    const ADMIN_SETTINGS_CONTROLLER = 'AdminDPDBalticsSettings';
    const ADMIN_SHIPMENT_SETTINGS_CONTROLLER = 'AdminDPDBalticsShipmentSettings';
    const ADMIN_IMPORT_EXPORT_CONTROLLER = 'AdminDPDBalticsImportExport';
    const ADMIN_PRICE_RULES_CONTROLLER = 'AdminDPDBalticsPriceRules';
    const ADMIN_ADDRESS_TEMPLATE_CONTROLLER = 'AdminDPDBalticsAddressTemplate';
    const ADMIN_AJAX_CONTROLLER = 'AdminDPDBalticsAjax';
    const ADMIN_PUDO_AJAX_CONTROLLER = 'AdminDPDBalticsPudoAjax';
    const ADMIN_REQUEST_SUPPORT_CONTROLLER = 'AdminDPDBalticsRequestSupport';
    const ADMIN_AJAX_SHIPMENTS_CONTROLLER = 'AdminDPDBalticsAjaxShipments';
    const ADMIN_LOGS_CONTROLLER = 'AdminDPDBalticsLogs';
    const ADMIN_SHIPMENT_CONTROLLER = 'AdminDPDBalticsShipment';
    const ADMIN_ORDER_RETURN_CONTROLLER = 'AdminDPDBalticsOrderReturn';
    const ADMIN_COLLECTION_REQUEST_CONTROLLER = 'AdminDPDBalticsCollectionRequest';
    const ADMIN_COURIER_REQUEST_CONTROLLER = 'AdminDPDBalticsCourierRequest';
    const ADMIN_AJAX_ON_BOARD_CONTROLLER = 'AdminDPDAjaxOnBoard';
    const ADMIN_ORDERS_CONTROLLER = 'AdminOrders';


    /**
     * @var \DPDBaltics
     */
    private $module;

    public function __construct(ModuleFactory $moduleFactory)
    {
        $this->module = $moduleFactory->getModule();
    }

    /**
     * @return array
     */
    public function getTabs()
    {
        return [
            [
                'name' => [
                    'en' => $this->module->displayName
                ],
                'class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent_class_name' => 'AdminParentShipping',
                'parent' => 'AdminParentShipping',
                'visible' => true
            ],
            [
                'name' => [
                    'en' => 'Shipment'
                ],
                'class_name' => self::ADMIN_SHIPMENT_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Orders returns'
                ],
                'class_name' => self::ADMIN_ORDER_RETURN_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Collection request'
                ],
                'class_name' => self::ADMIN_COLLECTION_REQUEST_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Courier request'
                ],
                'class_name' => self::ADMIN_COURIER_REQUEST_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Addresses'
                ],
                'class_name' => self::ADMIN_ADDRESS_TEMPLATE_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Price rules'
                ],
                'class_name' => self::ADMIN_PRICE_RULES_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Products'
                ],
                'class_name' => self::ADMIN_PRODUCTS_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Products availability'
                ],
                'class_name' => self::ADMIN_PRODUCT_AVAILABILITY_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => false,
            ],
            [
                'name' => [
                    'en' => 'Zones'
                ],
                'class_name' => self::ADMIN_ZONES_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Shipment Settings'
                ],
                'class_name' => self::ADMIN_SHIPMENT_SETTINGS_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Basic Settings'
                ],
                'class_name' => self::ADMIN_SETTINGS_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => true,
            ],
            [
                'name' => [
                    'en' => 'Import'
                ],
                'class_name' => self::ADMIN_IMPORT_EXPORT_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => false,
            ],
            [
                'name' => [
                    'en' => 'Logs'
                ],
                'class_name' => self::ADMIN_LOGS_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => false,
            ],
            [
                'name' => [
                    'en' => 'Ajax'
                ],
                'class_name' => self::ADMIN_AJAX_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => false,
            ],
            [
                'name' => [
                    'en' => 'Pudo Ajax'
                ],
                'class_name' => self::ADMIN_PUDO_AJAX_CONTROLLER,
                'parent_class_name' => -1,
                'module_tab' => true,
                'visible' => false,
            ],
            [
                'name' => [
                    'en' => 'Request support'
                ],
                'class_name' => self::ADMIN_REQUEST_SUPPORT_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => false,
            ],
            [
                'name' => [
                    'en' => 'Shipment ajax'
                ],
                'class_name' => self::ADMIN_AJAX_SHIPMENTS_CONTROLLER,
                'parent_class_name' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'parent' => self::ADMIN_DPDBALTICS_MODULE_CONTROLLER,
                'module_tab' => true,
                'visible' => false,
            ],
            [
                'name' => [
                    'en' => 'Ajax on-board'
                ],
                'class_name' => self::ADMIN_AJAX_ON_BOARD_CONTROLLER,
                'parent_class_name' => -1,
                'visible' => false
            ],
        ];
    }

    /**
     * Filter visible tabs to handle in javascript for ps versiosns below 1704
     */
    public function getTabsClassNames($visible = true)
    {
        $filtered = [];
        $tabs = $this->getTabs();


        foreach ($tabs as $tab) {

            if (!$visible) {
                $filtered[] = $tab['class_name'];
            }
            if (isset($tab['visible']) && $tab['visible']) {
                $filtered[] = $tab['class_name'];
            }
        }
        return $filtered;
    }
}