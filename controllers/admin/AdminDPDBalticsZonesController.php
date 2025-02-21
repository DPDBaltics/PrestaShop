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

use Invertus\dpdBaltics\Adapter\ZoneAdapter;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\Exception\ZoneUpdateException;
use Invertus\dpdBaltics\Exception\ZoneValidateException;
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\OnBoard\Service\OnBoardService;
use Invertus\dpdBaltics\OnBoard\Service\OnBoardStepActionService;
use Invertus\dpdBaltics\Provider\ZoneRangeProvider;
use Invertus\dpdBaltics\Service\Export\ExportProvider;
use Invertus\dpdBaltics\Service\Export\ZoneExport;
use Invertus\dpdBaltics\Service\Zone\DeleteZoneService;
use Invertus\dpdBaltics\Service\Zone\UpdateZoneService;
use Invertus\dpdBaltics\Validate\Zone\ZoneRangeValidate;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsZonesController extends AbstractAdminController
{
    /**
     * @var DPDZone
     */
    protected $object;

    public function __construct()
    {
        $this->className = 'DPDZone';
        $this->table = DPDZone::$definition['table'];
        $this->identifier = DPDZone::$definition['primary'];

        parent::__construct();
    }

    /**
     * Initialize controller with custom data
     */
    public function init()
    {
        $this->initList();
        $this->initForm();

        parent::init();
    }

    /**
     * Add custom buttons to toolbar
     */
    public function initToolbar()
    {
        parent::initToolbar();

        if ($this->display != 'edit' && $this->display != 'add') {
            $this->toolbar_btn['import'] = [
                'href' => $this->context->link->getAdminLink(
                    ModuleTabs::ADMIN_IMPORT_EXPORT_CONTROLLER,
                    true,
                    [],
                    ['importContr' => Config::IMPORT_EXPORT_OPTION_ZONES]
                ),
                'desc' => $this->module->l('Import')
            ];
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        // if ajax call the do not execute custom setMedia()
        if ($this->isXmlHttpRequest()) {
            return;
        }

        /** @var $zoneRangeProvider ZoneRangeProvider */
        $zoneRangeProvider = $this->module->getModuleContainer()->get('invertus.dpdbaltics.provider.zone_range_provider');

        Media::addJsDef([
            'dpdbaltics' => [
                'messages' => [
                    'error' => [
                        'emptyZoneRangeValue' => $this->module->l('Zone ranges cannot have empty values'),
                        'emptyZoneName' => $this->module->l('Zone name cannot be empty'),
                        'emptyZoneRanges' => $this->module->l('No zone ranges are configured'),
                    ],
                    'success' => [
                        'saved' => $this->module->l('Successfully saved'),
                    ],
                ],
                'notifications' => [
                    'saveProgress' => $this->module->l('Saving...'),
                ],
                'url' => [
                    'zonesControllerUrl' => $this->context->link->getAdminLink(ModuleTabs::ADMIN_ZONES_CONTROLLER),
                ],
                'entity' => [
                    'zoneRanges' => $zoneRangeProvider->getZoneRangesForJS(),
                ],
            ],
        ]);

        $this->addJS($this->module->getPathUri().'views/js/admin/models/DPDZoneRangesData.js');
        $this->addJS($this->module->getPathUri().'views/js/admin/zones.js');
    }

    /**
     * Customize list
     *
     * @param int $idLlang
     * @param null $orderBy
     * @param null $orderWay
     * @param int $start
     * @param null $limit
     * @param bool $idLangShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getList($idLlang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        $allZones = $this->module->l('All zipcodes');
        $limitedZones = $this->module->l('Limited zip codes');
        $oneZone = $this->module->l('One zip code');

        $this->_select = 'GROUP_CONCAT(DISTINCT c.iso_code) AS `countries`,';
        $this->_select .= 'COUNT(dzr.id_dpd_zone_range) AS `ranges_count`,';

        $this->_select .= 'IF(COUNT(dzr.id_dpd_zone_range) = SUM(dzr.include_all_zip_codes), "' . $allZones . '", 
        IF(SUM(dzr.include_all_zip_codes) = 0 AND SUM(dzr.zip_code_from_numeric) = SUM(dzr.zip_code_to_numeric), 
        "' . $oneZone . '", "' . $limitedZones . '")
        ) AS `inclusion_type`,
        ';

        $this->_select .= 'IF(COUNT(dzr.id_dpd_zone_range) = SUM(dzr.include_all_zip_codes), 
        "'.pSQL(Config::COLOR_ALL_ZONES).'",
        IF(SUM(dzr.include_all_zip_codes) = 0 AND SUM(dzr.zip_code_from_numeric) = SUM(dzr.zip_code_to_numeric),
        "'.pSQL(Config::COLOR_ONE_ZONE).'", "'.pSQL(Config::COLOR_LIMITED_ZONES).'")
        )
        AS `color_value`
        ';

        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'dpd_zone_range` dzr ON dzr.id_dpd_zone = a.id_dpd_zone ';
        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'country` c ON c.`id_country`= dzr.`id_country`';

        $this->_group = 'GROUP BY a.id_dpd_zone';

        parent::getList($idLlang, $orderBy, $orderWay, $start, $limit, $idLangShop);
    }

    /**
     * Render form with prefilled values
     *
     * @return string
     */
    public function renderForm()
    {
        $countries = Country::getCountries($this->context->language->id);

        if (empty($countries)) {
            $this->errors[] =  $this->module->l('Countries cannot be loaded');
            return false;
        }

        $this->context->smarty->assign('countryList', $countries);

        $this->fields_value['id_dpd_zone'] = (int) $this->object->id;
        $this->fields_value['zone_name'] = (string) $this->object->name;
        $this->fields_value['ranges'] = $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/zone-ranges.tpl'
        );

        $this->fields_value['alerts'] = $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/alert-block.tpl'
        );

        return parent::renderForm();
    }

    /**
     * Process AJAX saving
     */
    public function postProcess()
    {
        if (!$this->isXmlHttpRequest()) {
            return parent::postProcess();
        }

        $zoneId = Tools::getValue('zone_id');
        $zoneName = Tools::getValue('zone_name');
        $zoneRanges = Tools::getValue('zone_ranges');

        $response['status'] = 1;
        $response['id_dpd_zone'] = $zoneId;

        /** @var $updateZoneService UpdateZoneService */
        /** @var $zoneAdapter ZoneAdapter */
        /** @var $zoneRangeValidate ZoneRangeValidate */
        $updateZoneService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.zone.update_zone_service');
        $zoneAdapter = $this->module->getModuleContainer()->get('invertus.dpdbaltics.adapter.zone_adapter');
        $zoneRangeValidate = $this->module->getModuleContainer()->get('invertus.dpdbaltics.validate.zone.zone_range_validate');

        $zoneRanges = $zoneAdapter->convertZoneRangesToObjects($zoneRanges);

        try {
            $zoneRangeValidate->validateZoneRanges($zoneId, $zoneName, $zoneRanges);
            $response['id_dpd_zone'] = $updateZoneService->updateZone($zoneId, $zoneName, $zoneRanges);
            $response = $this->updateOnBoardAndAddTemplateToResponseOnZoneSave($response);
        } catch (ZoneValidateException $e) {
            $response['status'] = 0;
            $response['error'] = $e->getMessage();
        } catch (ZoneUpdateException $e) {
            $response['status'] = 0;
            $response['error'] = $e->getMessage();
        } catch (Exception $e) {
            $response['status'] = 0;
            $response['error'] = $e->getMessage();
        }

        $this->ajaxDie(json_encode($response));
    }

    public function processDelete()
    {
        $zoneId = (int) Tools::getValue('id_dpd_zone');

        /** @var $deleteZoneService DeleteZoneService */
        $deleteZoneService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.zone.delete_zone_service');

        try {
            $deleteZoneService->deleteZone($zoneId);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        $this->errors = array_merge($this->errors, $deleteZoneService->getErrors());
        $this->confirmations = array_merge($this->confirmations, $deleteZoneService->getConfirmation());
    }

    public function processBulkDelete()
    {
        $zonesId = (array) Tools::getValue($this->table.'Box');

        if (empty($zonesId)) {
            return parent::processBulkDelete();
        }

        /** @var $deleteZoneService DeleteZoneService */
        $deleteZoneService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.zone.delete_zone_service');

        try {
            $deleteZoneService->bulkDeleteZones($zonesId);
        } catch (Exception $e) {
            $this->errors[] =  $e->getMessage();
            return false;
        }

        $this->errors = array_merge($this->errors, $deleteZoneService->getErrors());
        $this->confirmations = array_merge($this->confirmations, $deleteZoneService->getConfirmation());
    }

    /**
     * List definition
     */
    private function initList()
    {
        $this->list_no_link = true;
        $this->allow_export = true;
        $this->can_import = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->module->l('Delete selected'),
                'confirm' => $this->module->l('Delete selected items?'),
                'icon' => 'icon-trash'
            ]
        ];

        $this->fields_list = [
            'name' => [
                'title' => $this->module->l('Name'),
                'type' => 'text',
                'align' => 'center',
            ],
            'countries' => [
                'title' => $this->module->l('Countries'),
                'type' => 'text',
                'align' => 'center',
                'havingFilter' => true,
            ],
            'ranges_count' => [
                'title' => $this->module->l('Ranges'),
                'type' => 'text',
                'align' => 'center',
                'havingFilter' => true,
            ],
            'inclusion_type' => [
                'title' => $this->module->l('Inclusion'),
                'type' => 'text',
                'align' => 'center',
                'color' => 'color_value',
                'havingFilter' => true,
            ],
        ];
    }

    /**
     * Form definition
     */
    private function initForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Zone'),
            ],
            'input' => [
                [
                    'label' => '',
                    'type' => 'free',
                    'name' => 'alerts',
                ],
                [
                    'label' => $this->module->l('Name'),
                    'type' => 'text',
                    'name' => 'zone_name',
                    'required' => true,
                    'class' => 'fixed-width-xxl js-dpd-zone-name',
                ],
                [
                    'label' => '',
                    'type' => 'hidden',
                    'name' => 'id_dpd_zone',
                ],
                [
                    'label' => '',
                    'type' => 'free',
                    'name' => 'ranges',
                ],
            ],
            'buttons' => [
                [
                    'title' => $this->module->l('Save'),
                    'icon' => 'process-icon-save',
                    'name' => 'processSaveZoneRanges',
                    'type' => 'button',
                    'class' => 'pull-right',
                ],
                [
                    'title' => $this->module->l('Save and stay'),
                    'icon' => 'process-icon-save',
                    'name' => 'processSaveAndStayZoneRanges',
                    'type' => 'button',
                    'class' => 'pull-right',
                ],
            ],
        ];
    }

    private function updateOnBoardAndAddTemplateToResponseOnZoneSave($response)
    {
        if (Configuration::get(Config::ON_BOARD_TURNED_ON) &&
            Configuration::get(Config::ON_BOARD_STEP) === Config::STEP_MANUAL_ZONES_5
        ) {
            /** @var OnBoardStepActionService $onBoardStepActionService */
            $onBoardStepActionService = $this->module->getModuleContainer('invertus.dpdbaltics.on_board.service.on_board_step_action_service');
            $onBoardStepActionService->nextStep(Config::STEP_MANUAL_ZONES_6);

            if(Tools::getValue('buttonName') === Config::ZONES_SAVE_AND_STAY_BUTTON) {
                /** @var OnBoardService $onBoardService */
                $onBoardService = $this->module->getModuleContainer('invertus.dpdbaltics.on_board.service.on_board_service');
                $response['onBoardStepTemplate'] = $onBoardService->makeStepActionWithTemplateReturn(true);
            }
        }

        return $response;
    }

}
