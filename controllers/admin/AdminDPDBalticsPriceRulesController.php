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

use Invertus\dpdBaltics\Builder\Template\SearchBoxBuilder;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\Repository\DPDZoneRepository;
use Invertus\dpdBaltics\Repository\ShopRepository;
use Invertus\dpdBaltics\Service\DPDFlashMessageService;
use Invertus\dpdBaltics\Service\PriceRuleService;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsPriceRulesController extends AbstractAdminController
{
    /** @var  DPDPriceRule */
    protected $object;

    protected $position_identifier = 'id_dpd_price_rule';

    /** @var PriceRuleService */
    private $priceRuleService;

    public function __construct()
    {
        $this->allow_export = true;
        $this->can_import = true;
        $this->className = 'DPDPriceRule';
        $this->table = DPDPriceRule::$definition['table'];
        $this->identifier = DPDPriceRule::$definition['primary'];
        $this->lang = false;

        parent::__construct();

        $this->initList();
        $this->initForm();
        $this->priceRuleService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.price_rule_service');
    }

    /**
     * Add custom CSS & JS
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        Media::addJsDef([
            'checkAllMessage' => $this->module->l('Check all'),
            'uncheckAllMessage' => $this->module->l('Uncheck all')
        ]);
        $this->addJS($this->getModuleJSUri() . 'price_rules.js');
    }

    public function postProcess()
    {
        if (Tools::getIsset('duplicatePriceRule')) {
            $idPriceRule = Tools::getValue('idPriceRule');

            if (!$this->priceRuleService->duplicatePriceRule($idPriceRule)) {
                /** @var DPDFlashMessageService $flashMessageService */
                $flashMessageService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.dpdflash_message_service');

                $msg = $this->module->l('Price rule duplication failed. Data is not saved.');
                $flashMessageService->addFlash('error', $msg);

                $this->redirect_after =
                    $this->context->link->getAdminLink($this->controller_name, true);
            } else {
                $this->redirect_after =
                    $this->context->link->getAdminLink($this->controller_name, true, [], ['conf' => 19]);
            }
        }

        if (Tools::isSubmit('submitAdddpd_price_ruleAndStay') ||
            Tools::isSubmit('submitAdddpd_price_rule')
        ) {
            $_POST['order_price_from'] = str_replace(',', '.', Tools::getValue('order_price_from'));
            $_POST['order_price_to'] = str_replace(',', '.', Tools::getValue('order_price_to'));
            $_POST['weight_from'] = str_replace(',', '.', Tools::getValue('weight_from'));
            $_POST['weight_to'] = str_replace(',', '.', Tools::getValue('weight_to'));
            $_POST['price'] = str_replace(',', '.', Tools::getValue('price'));
        }

        parent::postProcess();
    }

    public function initToolbar()
    {
        parent::initToolbar();

        if ($this->display !== 'edit' && $this->display !== 'add') {
            $this->toolbar_btn['import'] = [
                'href' => $this->context->link->getAdminLink(
                    'AdminDPDBalticsImportExport',
                    true,
                    [],
                    ['importContr' => Config::IMPORT_EXPORT_OPTION_PRICE_RULES]
                ),
                'desc' => $this->module->l('Import'),
            ];
        }
    }

    public function renderForm()
    {
        $this->context->smarty->assign([
            'carrierName' => $this->module->l('Carriers'),
            'paymentsName' => $this->module->l('Payment methods'),
            'checkbox_list_dir' => 'file:' . $this->module->getLocalPath() . 'views/templates/admin/checkbox-list.tpl',
        ]);

        $this->priceRuleService->loadCarriers($this->object);
        $this->priceRuleService->loadPayments($this->object);

        if (Validate::isLoadedObject($this->object)) {
            $this->assignTemplateVars();
        } else {
            $this->fields_value['customer_type'] = DPDPriceRule::CUSTOMER_TYPE_ALL;
        }
        $this->show_form_cancel_button = Config::IS_CANCEL_BUTTON_DEFAULT;
        $this->getFieldFormVars();

        return parent::renderForm();
    }

    public function renderList()
    {
        $this->_select = 'IF(prp.`all_payments`=1, "All", GROUP_CONCAT(DISTINCT(m.`name`))) AS `payment_method`,';

        $this->_select .= '
            IF(
                a.`customer_type`="' . pSQL(DPDPriceRule::CUSTOMER_TYPE_ALL) . '","' . pSQL(Config::COLOR_INFO) . '",
                IF(a.`customer_type`="' . pSQL(DPDPriceRule::CUSTOMER_TYPE_REGULAR) . '","' . pSQL(Config::COLOR_SUCCESS) . '",
                "' . pSQL(Config::COLOR_WARNING) . '"
                )
                )
            AS `color_value`,
        ';

        $this->_select .= 'IF(
                a.`customer_type`="' . pSQL(DPDPriceRule::CUSTOMER_TYPE_ALL) . '","' . pSQL($this->module->l('All')) . '",
                IF(a.`customer_type`="' . pSQL(DPDPriceRule::CUSTOMER_TYPE_REGULAR) . '","' . pSQL($this->module->l('Individual')) . '",
                "' . pSQL($this->module->l('Company')) . '"
                )
                )
            AS `customer_type`,';

        $this->_select .=
            'IF(a.`order_price_from` = "0" AND a.`order_price_to` = "0", "-", a.`order_price_from`) 
            AS `order_price_from`,';
        $this->_select .=
            'IF(a.`order_price_from` = "0" AND a.`order_price_to` = "0", "-", a.`order_price_to`) AS `order_price_to`,';
        $this->_select .=
            'IF(a.`weight_from` = "0" AND a.`weight_to` = "0", "-", a.`weight_from`) AS `weight_from`,';
        $this->_select .=
            'IF(a.`weight_from` = "0" AND a.`weight_to` = "0", "-", a.`weight_to`) AS `weight_to`';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . pSQL(DPDPriceRule::$definition['table']) . '_payment` prp
                            ON (prp.`id_dpd_price_rule` = a.`id_dpd_price_rule`)';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'module` m
                            ON m.`id_module`= prp.`id_module`';
        $this->_group = 'GROUP BY a.`id_dpd_price_rule`';

        return parent::renderList();
    }

    public function afterAdd($object)
    {
        $this->updateOnBoardStep();
        $this->priceRuleService->addUpdateCarriers($object);
        $this->priceRuleService->addUpdateZones($object);
        $this->priceRuleService->removeShops($object->id);
        $this->priceRuleService->addUpdateShops($object);
        $this->priceRuleService->addUpdatePaymentMethods($object);
        $this->errors = array_merge($this->errors, $this->priceRuleService->getErrors());

        return parent::afterAdd($object);
    }

    public function afterUpdate($object)
    {
        $this->updateOnBoardStep();
        $this->priceRuleService->removeCarriers((int)$object->id);
        $this->priceRuleService->removePaymentMethods((int)$object->id);
        $this->priceRuleService->removeZones((int)$object->id);
        $this->priceRuleService->removeShops((int)$object->id);
        $this->priceRuleService->addUpdateCarriers($object);
        $this->priceRuleService->addUpdateZones($object);
        $this->priceRuleService->addUpdateShops($object);
        $this->priceRuleService->addUpdatePaymentMethods($object);
        $this->errors = array_merge($this->errors, $this->priceRuleService->getErrors());

        return parent::afterUpdate($object);
    }

    public function processDelete()
    {
        $idPriceRule = (int)Tools::getValue('id_dpd_price_rule');
        $this->priceRuleService->removeCarriers($idPriceRule);
        $this->priceRuleService->removePaymentMethods($idPriceRule);
        $this->priceRuleService->removeZones($idPriceRule);
        $this->priceRuleService->removeShops($idPriceRule);
        $this->errors = array_merge($this->errors, $this->priceRuleService->getErrors());

        return parent::processDelete();
    }

    public function processBulkDelete()
    {
        $priceRules = (array)Tools::getValue($this->table . 'Box');
        if (empty($priceRules)) {
            return parent::processBulkDelete();
        }
        foreach ($priceRules as $ruleId) {
            $this->priceRuleService->removeCarriers((int)$ruleId);
            $this->priceRuleService->removePaymentMethods((int)$ruleId);
            $this->priceRuleService->removeZones((int)$ruleId);
        }

        return parent::processBulkDelete();
    }

    public function ajaxProcessUpdatePositions()
    {
        $way = (int)(Tools::getValue('way'));
        $idPriceRule = (int)(Tools::getValue('id'));
        $positions = Tools::getValue($this->table);

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int)$pos[2] === $idPriceRule) {
                if ($priceRuleObject = new DPDPriceRule((int)$pos[2])) {
                    if (isset($position) && $priceRuleObject->updatePosition($way, $position)) {
                        echo sprintf(
                            $this->module->l('Ok position %s for Dpd price rule %d'),
                            (int)$position,
                            (int)$pos[1]
                        );
                    } else {
                        $message = sprintf(
                            $this->module->l('Can not update Dpd price rule %s to position %d'),
                            (int)$idPriceRule,
                            (int)$position
                        );
                        echo $this->displayAjaxErrorMessage($message);
                    }
                } else {
                    $message = sprintf(
                        $this->module->l('This Dpd price rule %s can\'t be loaded'),
                        (int)$idPriceRule
                    );
                    echo $this->displayAjaxErrorMessage($message);
                }
                break;
            }
        }
    }

    protected function _childValidation()
    {
        $dpdCarriers = Tools::getValue('dpd-carrier');
        $paymentMethods = Tools::getValue('dpd-payment-method');
        $selectedZones = Tools::getValue('zones_select');

        if (!$selectedZones) {
            $this->errors[] = $this->module->l('Please select at least one receiver zone.');
        }

        if (!$dpdCarriers) {
            $this->errors[] = $this->module->l('Please select at least one carrier.');
        }

        if (!$paymentMethods) {
            $this->errors[] = $this->module->l('Please select at least one payment method.');
        }

        if (Tools::getValue('order_price_from') || Tools::getValue('order_price_to')) {
            $this->priceRuleService->validatePriceRanges(
                (float)Tools::getValue('order_price_from'),
                (float)Tools::getValue('order_price_to')
            );
        }

        if (Tools::getValue('weight_from') || Tools::getValue('weight_to')) {
            $this->priceRuleService->validateWeightRanges(
                (float)Tools::getValue('weight_from'),
                (float)Tools::getValue('weight_to')
            );
        }
        if (!empty($this->errors)) {
            $this->assignTemplateVars();
            return false;
        }
        parent::_childValidation();
    }

    private function assignTemplateVars()
    {
        $this->context->smarty->assign([
            'priceFrom' => (float)(isset($this->object->order_price_from)) ?
                $this->object->order_price_from :
                Tools::getValue('order_price_from'),
            'priceTo' => (float)(isset($this->object->order_price_to)) ?
                $this->object->order_price_to :
                Tools::getValue('order_price_to'),
            'weightFrom' => (float)(isset($this->object->weight_from)) ?
                $this->object->weight_from :
                Tools::getValue('weight_from'),
            'weightTo' => (float)(isset($this->object->weight_to)) ?
                $this->object->weight_to :
                Tools::getValue('weight_to')
        ]);
    }

    private function getFieldFormVars()
    {
        $this->fields_value['price_rule_ranges'] =
            $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/price-rule-ranges.tpl');
        $this->fields_value['carriers_box'] =
            $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/carriers-box.tpl');
        $this->fields_value['payment_methods'] =
            $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/payment-box.tpl');

        /** @var DPDZoneRepository $zoneRepository */
        $zoneRepository = $this->module->getModuleContainer()->get('invertus.dpdbaltics.repository.dpdzone_repository');
        $priceRuleZones = $zoneRepository->getSelectedPriceRuleZones($this->object->id);

        /** @var ShopRepository $shopRepository */
        $shopRepository = $this->module->getModuleContainer()->get('invertus.dpdbaltics.repository.shop_repository');
        $priceRuleShops = $shopRepository->getPriceRuleShops($this->object->id);

        if (empty($priceRuleZones)) {
            $this->fields_value['search_block'] = $this->getMessageWithLink(
                'warning',
                $this->context->link->getAdminLink(ModuleTabs::ADMIN_ZONES_CONTROLLER) .
                '&add' . DPDZone::$definition['table'],
                $this->module->l('There are no zones to add. '),
                $this->module->l(' to add more zones.')
            );
        } else {
            $allZonesSelected = false;

            // Checking if "All zones" option is selected
            foreach ($priceRuleZones as $priceRuleZone) {
                if ($priceRuleZone['all_selected_flag']) {
                    $allZonesSelected = true;
                    break;
                }
            }

            $searchBoxName = 'zones_select[]';

            /** @var SearchBoxBuilder $searchBoxBuilder */
            $searchBoxBuilder = $this->module->getModuleContainer()->get('invertus.dpdbaltics.builder.template.search_box_builder');
            $searchBoxPlugin = $searchBoxBuilder->createSearchBox(
                $priceRuleZones,
                $allZonesSelected,
                $searchBoxName
            );

            $this->addCSS($searchBoxPlugin->getCss());
            $this->addJS($searchBoxPlugin->getJs());
            Media::addJsDef($searchBoxPlugin->getJsVars());

            $this->fields_value['search_block'] = $searchBoxPlugin->render();
        }

        $shops = Shop::getShops(true);
        $isMultiShop = (bool) Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && (count($shops) > 1);

        if ($isMultiShop) {
            $allShopsSelected = false;

            // Checking if "All shops" option is selected
            foreach ($priceRuleShops as $priceRuleShop) {
                if ($priceRuleShop['all_selected_flag']) {
                    $allShopsSelected = true;
                    break;
                }
            }

            $searchBoxName = 'shops_select[]';

            /** @var SearchBoxBuilder $searchBoxBuilder */
            $searchBoxBuilder = $this->module->getModuleContainer()->get('invertus.dpdbaltics.builder.template.search_box_builder');
            $searchBoxPlugin = $searchBoxBuilder->createSearchBox(
                $priceRuleShops,
                $allShopsSelected,
                $searchBoxName
            );

            $this->addCSS($searchBoxPlugin->getCss());
            $this->addJS($searchBoxPlugin->getJs());
            Media::addJsDef($searchBoxPlugin->getJsVars());

            $this->fields_value['search_block_shops'] = $searchBoxPlugin->render();
        }

        if (!Validate::isLoadedObject($this->object)) {
            $this->fields_value['active'] = 1;
        }

        $this->fields_value['price'] = (float)(isset($this->object->price) && $this->object->price) ?
            $this->object->price :
            Tools::getValue('price');
    }

    private function initList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('duplicatePriceRule');
        $this->addRowAction('delete');
        $this->_defaultOrderBy = 'position';

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
                'filter_key' => 'a!name',
            ],
            'customer_type' => [
                'title' => $this->module->l('Customer type'),
                'type' => 'select',
                'list' => [
                    DPDPriceRule::CUSTOMER_TYPE_ALL => $this->module->l('All'),
                    DPDPriceRule::CUSTOMER_TYPE_REGULAR => $this->module->l('Individual'),
                    DPDPriceRule::CUSTOMER_TYPE_COMPANY => $this->module->l('Company'),
                ],
                'align' => 'center',
                'color' => 'color_value',
                'filter_key' => 'a!customer_type',
            ],
            'order_price_from' => [
                'title' => $this->module->l('Order price from'),
                'type' => 'price',
                'align' => 'center',
                'havingFilter' => true,
                'filter_key' => 'a!order_price_from'
            ],
            'order_price_to' => [
                'title' => $this->module->l('Order price to'),
                'type' => 'price',
                'align' => 'center',
                'havingFilter' => true,
                'filter_key' => 'a!order_price_to'
            ],
            'weight_from' => [
                'title' => $this->module->l('Weight from'),
                'type' => 'weight',
                'align' => 'center',
                'havingFilter' => true,
                'filter_key' => 'a!weight_from'
            ],
            'weight_to' => [
                'title' => $this->module->l('Weight to'),
                'type' => 'weight',
                'align' => 'center',
                'havingFilter' => true,
                'filter_key' => 'a!weight_to'
            ],
            'price' => [
                'title' => $this->module->l('Price'),
                'type' => 'price',
                'align' => 'center',
                'filter_key' => 'a!price'
            ],
            'payment_method' => [
                'title' => $this->module->l('Payment methods'),
                'type' => 'text',
                'align' => 'center',
                'havingFilter' => true
            ],
            'position' => [
                'filter' => false,
                'search' => false,
                'title' => $this->module->l('Position'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'position' => 'position',
            ],
            'active' => [
                'title' => $this->module->l('Status'),
                'align' => 'text-center',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'filter_key' => 'a!active',
            ]
        ];
    }

    private function initForm()
    {
        $redirectUrl = $this->context->link->getAdminLink('AdminDPDBalticsPriceRules');
        $shops = Shop::getShops(true);
        $isMultiShop = (bool) Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && (count($shops) > 1);

        $inputs = [
            [
                'type' => 'switch',
                'label' => $this->module->l('Active'),
                'name' => 'active',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->module->l('Enabled')
                    ],
                    [
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->module->l('Disabled')
                    ]
                ],
            ],
            [
                'type' => 'radio',
                'label' => $this->module->l('Customer types'),
                'name' => 'customer_type',
                'hint' => $this->module->l('Do you want this rule to apply everybody or just companies or individuals?'),
                'required' => true,
                'is_bool' => true,
                'form_group_class' => 'dpd-price-rule-customer-types',
                'values' => [
                    [
                        'id' => 'customer_type' . DPDPriceRule::CUSTOMER_TYPE_ALL,
                        'value' => DPDPriceRule::CUSTOMER_TYPE_ALL,
                        'label' => $this->module->l('All')
                    ],
                    [
                        'id' => 'customer_type' . DPDPriceRule::CUSTOMER_TYPE_REGULAR,
                        'value' => DPDPriceRule::CUSTOMER_TYPE_REGULAR,
                        'label' => $this->module->l('Individual')
                    ],
                    [
                        'id' => 'customer_type' . DPDPriceRule::CUSTOMER_TYPE_COMPANY,
                        'value' => DPDPriceRule::CUSTOMER_TYPE_COMPANY,
                        'label' => $this->module->l('Company')
                    ],
                ]
            ],
            [
                'label' => $this->module->l('Name'),
                'hint' => $this->module->l('Name of the price rule is displayed only in back office'),
                'name' => 'name',
                'required' => true,
                'type' => 'text',
                'col' => '3',
                'form_group_class' => 'dpd-price-rule-name',
            ],
            [
                'label' => '',
                'name' => 'price_rule_ranges',
                'type' => 'free',
                'form_group_class' => 'dpd-price-rule-ranges',
            ],
            [
                'label' => $this->module->l('Carriers'),
                'name' => 'carriers_box',
                'type' => 'free',
                'form_group_class' => 'dpd-price-rule-carriers',
                'required' => true,
            ],
            [
                'label' => $this->module->l('Receiver zones'),
                'name' => 'search_block',
                'type' => 'free',
                'form_group_class' => 'dpd-price-rule-zones',
                'required' => true
            ]
        ];

        if ($isMultiShop) {
            $inputs[] = [
                'label' => $this->module->l('Receiver shops'),
                'name' => 'search_block_shops',
                'type' => 'free',
                'form_group_class' => 'dpd-price-rule-shops',
            ];
        }

        $this->multiple_fieldsets = true;
        $this->fields_form = [];
        $this->fields_form[] = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Conditions'),
                    'icon' => 'icon-cogs'
                ],
                'input' => $inputs,
                'submit' => [
                    'title' => $this->module->l('Save'),
                ],
                'buttons' => [
                    [
                        'title' => $this->module->l('Save and stay'),
                        'icon' => 'process-icon-save',
                        'name' => 'submitAdddpd_price_ruleAndStay',
                        'class' => 'pull-right',
                        'type' => 'submit'
                    ],
                    [
                        'title' => $this->module->l('Cancel'),
                        'icon' => 'process-icon-cancel',
                        'label' => 'back button',
                        'name' => 'back_button',
                        'type' => 'free',
                        'href' => $redirectUrl
                    ],
                ],
            ],
        ];
        $this->fields_form[] = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Actions'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'label' => $this->module->l('Set shipping price to'),
                        'name' => 'price',
                        'type' => 'text',
                        'col' => '3',
                        'form_group_class' => 'dpd-price-rule-shipping-price',
                        'suffix' => $this->context->currency->sign
                    ],
                    [
                        'label' => $this->module->l('Enabled payment methods'),
                        'name' => 'payment_methods',
                        'type' => 'free',
                        'form_group_class' => 'dpd-price-rule-payment-methods',
                        'required' => true
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save'),
                ],
                'buttons' => [
                    [
                        'title' => $this->module->l('Save and stay'),
                        'icon' => 'process-icon-save',
                        'name' => 'submitAdddpd_price_ruleAndStay',
                        'class' => 'pull-right',
                        'type' => 'submit'
                    ],
                    [
                        'title' => $this->module->l('Cancel'),
                        'icon' => 'process-icon-cancel',
                        'label' => 'back button',
                        'name' => 'back_button',
                        'type' => 'free',
                        'href' => $redirectUrl
                    ],
                ],

            ],
        ];
    }

    /**
     * @return bool|void
     */
    protected function updateAssoShop($id_object)
    {
        return;
    }

    private function displayAjaxErrorMessage($message)
    {
        return '{"hasError" : true,"errors" : "' . $message . '"}';
    }

    private function updateOnBoardStep()
    {
        if (Configuration::get(Config::ON_BOARD_TURNED_ON) &&
            Configuration::get(Config::ON_BOARD_STEP) === Config::STEP_MANUAL_PRICE_RULES_8
        ) {
            Configuration::updateValue(Config::ON_BOARD_STEP, Config::STEP_MANUAL_CONFIG_FINISH);
        }
    }
}
