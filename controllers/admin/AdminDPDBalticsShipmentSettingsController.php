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
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\Repository\AddressTemplateRepository;
use Invertus\dpdBaltics\Repository\CodPaymentRepository;
use Invertus\dpdBaltics\Repository\PaymentRepository;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Service\Label\LabelPositionService;
use Invertus\dpdBaltics\Templating\InfoBlockRender;
use Invertus\dpdBaltics\Util\CountryUtility;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsShipmentSettingsController extends AbstractAdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->override_folder = 'field-option-swap/';
        $this->tpl_folder = 'field-option-swap/';
    }

    public function postProcess()
    {
        if (!Tools::isSubmit('submitOptionsconfiguration')) {
            return parent::postProcess();
        }

        parent::postProcess();

        // set label position to 1 if format is A6
        $isA4Format = strpos(Configuration::get(Config::DEFAULT_LABEL_FORMAT), 'A4');
        if (false === $isA4Format) {
            Configuration::updateValue(Config::DEFAULT_LABEL_POSITION, 1);
        }

        if (!empty($this->errors)) {
            return;
        }

        /** @var CodPaymentRepository $codPaymentRepo */
        $codPaymentRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.cod_payment_repository');

        if (!$codPaymentRepo->removeCodPaymentModules()) {
            $this->errors[] = $this->module->l('Failed to delete COD payment methods');
        }

        if (Tools::getIsset('payments_selected')) {
            $codPaymentModules = Tools::getValue('payments_selected');

            $codPaymentsSqlArray = [];

            foreach ($codPaymentModules as $codPaymentModule) {
                $codPaymentsSqlArray[] = '(' . (int)$codPaymentModule . ')';
            }

            if (!$codPaymentRepo->addCodPaymentModules($codPaymentsSqlArray)) {
                $this->errors[] = $this->module->l('Failed to add COD payment methods');
            }
        }

        if (empty($this->errors)) {
            $this->redirect_after =
                $this->context->link->getAdminLink($this->controller_name, true, [], ['conf' => 6]);
        } else {
            $this->confirmations = [];
        }
    }

    /**
     * Initialize controller with options
     */
    public function init()
    {
        $parentReturn = parent::init();
        $this->initOptions();

        if (!Tools::isSubmit('submitOptionsconfiguration')) {
            return $parentReturn;
        }

        parent::init();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS($this->getModuleJSUri() . 'pickup_map_settings.js');
        $this->addJS($this->getModuleJSUri() . 'swap.js');
        $this->addJS($this->getModuleJSUri() . 'custom_select.js');
        $this->addJS($this->getModuleJSUri() . 'label_position.js');
        $this->addJS($this->getModuleJSUri() . 'shipment_settings.js');
        $this->addCSS($this->getModuleCssUri() . 'customSelect/custom-select.css');
    }

    /**
     * Options form definition
     */
    protected function initOptions()
    {
        /** @var AddressTemplateRepository $addressTemplateRepo */
        $addressTemplateRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.address_template_repository');
        /** @var InfoBlockRender $infoBlockRender */
        $infoBlockRender = $this->module->getModuleContainer()->get('invertus.dpdbaltics.templating.info_block_render');

        $infoBlockText = $this->module->l('Please move COD modules to the right, non-COD modules leave on the left');
        $returnServiceAddresses = $addressTemplateRepo->getReturnServiceAddressTemplates();
        if (!$returnServiceAddresses) {
            $addressTabRedirect = $this->context->link->getAdminLink(ModuleTabs::ADMIN_ADDRESS_TEMPLATE_CONTROLLER);
            $this->context->smarty->assign(
                [
                    'addressTabRedirect' => $addressTabRedirect
                ]
            );

            if (Configuration::get(Config::PARCEL_RETURN)) {
                $this->warnings[] =
                    sprintf(
                        $this->module->l('To allow returns for your customers, you need to create return %s address %s in Addresses tab'),
                        $this->module->display($this->module->getPathUri(), 'views/templates/admin/redirect/redirect-opening.tpl'),
                        $this->module->display($this->module->getPathUri(), 'views/templates/admin/redirect/redirect-closing.tpl')
                    );
            }

        }

        /** @var LabelPositionService $labelPositionService */
        $labelPositionService = $this->module->getModuleContainer('invertus.dpdbaltics.service.label.label_position_service');

        /** @var CodPaymentRepository $codPaymentRepo */
        $codPaymentRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.cod_payment_repository');
        $codPaymentModules = $codPaymentRepo->getCodPaymentModules();
        $this->context->smarty->assign('swap', [
            'name' => 'payments',
            'disabled' => !$this->getPaymentMethods() ? true : false,
            'fields_value' => $codPaymentModules,
            'options' => [
                'query' => $this->getPaymentMethods(),
                'id' => 'id_module',
                'name' => 'display_name',
                'type' => 'name'
            ]
        ]);

        $parcelReturn = [
            'title' => $this->module->l('Parcel return'),
            'validation' => 'isBool',
            'cast' => 'intval',
            'type' => 'bool'
        ];

        if (CountryUtility::isEstonia()) {
            $parcelReturn = [
                'title' => $this->module->l('Parcel return'),
                'validation' => 'isBool',
                'cast' => 'intval',
                'type' => 'hidden',
                'disabled' => true
            ];
        }

        $this->context->smarty->assign('googleMapsApiKeyLink', Config::GOOGLE_MAPS_API_KEY_LINK);
        $this->fields_options = [
            'shipping_configuration' => [
                'title' => $this->module->l('Shipping configuration'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::PARCEL_TRACKING => [
                        'title' => $this->module->l('Parcel tracking'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool',
                    ],
                    Config::PARCEL_RETURN => $parcelReturn,
                    Config::DOCUMENT_RETURN => [
                        'title' => $this->module->l('Document return'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool',
                    ],
                    Config::PICKUP_MAP => [
                        'title' => $this->module->l('Pickup map'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool',
                    ],
                    Config::AUTOMATED_PARCEL_RETURN => [
                        'title' => $this->trans('Automatically generate return label'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'hint' => $this->trans('Enabling automated parcel returns will generate a return label for every DPD order', [], 'Admin.Shipment'),
                        'type' => 'bool',
                    ],
                    Config::GOOGLE_API_KEY => [
                        'title' => $this->module->l('Google maps Api key'),
                        'validation' => 'isCleanHtml',
                        'type' => 'text',
                        'class' => 'fixed-width-xxl',
                        'desc' => $this->context->smarty->fetch($this->getTemplatePath() . 'api-key-description.tpl'),
                    ],
                    Config::PARCEL_SHOP_DISPLAY => [
                        'title' => $this->module->l('Pickup display'),
                        'type' => 'radio',
                        'choices' => [
                            Config::PARCEL_SHOP_DISPLAY_LIST
                            => $this->module->l('Show pickup points in list'),
                            Config::PARCEL_SHOP_DISPLAY_BLOCK
                            => $this->module->l('Show pickup points in blocks'),
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save'),
                ],
            ],
            'parcel_configuration' => [
                'title' => $this->module->l('Shipment configuration'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::PARCEL_DISTRIBUTION => [
                        'title' => $this->module->l('Product distribution'),
                        'type' => 'radio',
                        'choices' => [
                            DPDParcel::DISTRIBUTION_NONE
                            => $this->module->l('All products in same shipment'),
                            DPDParcel::DISTRIBUTION_PARCEL_PRODUCT
                            => $this->module->l('Each product in separate parcel'),
                            DPDParcel::DISTRIBUTION_PARCEL_QUANTITY
                            => $this->module->l('Each product quantity in separate parcel'),
                        ],
                    ],
                    Config::AUTO_VALUE_FOR_REF => [
                        'title' => $this->module->l('Automatic value for Reference 1 in Shipment'),
                        'type' => 'select',
                        'list' => [
                            [
                                'value' => DPDShipment::AUTO_VAL_REF_NONE,
                                'name' => $this->module->l('None'),
                            ],
                            [
                                'value' => DPDShipment::AUTO_VAL_REF_ORDER_ID,
                                'name' => $this->module->l('Order Id'),
                            ],
                            [
                                'value' => DPDShipment::AUTO_VAL_REF_ORDER_REF,
                                'name' => $this->module->l('Order Reference'),
                            ],
                        ],
                        'identifier' => 'value',
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save'),
                ],
            ],
            'label_configuration' => [
                'title' => $this->module->l('Label configuration'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::LABEL_PRINT_OPTION => [
                        'title' => $this->module->l('Label print option'),
                        'desc' => $this->module->l('Define label to be printed directly from browser or downloaded'),
                        'type' => 'select',
                        'list' => [
                            [
                                'value' => Config::PRINT_OPTION_DOWNLOAD,
                                'name' => $this->module->l('Download'),
                            ],
                            [
                                'value' => Config::PRINT_OPTION_BROWSER,
                                'name' => $this->module->l('Print from browser'),
                            ],
                        ],
                        'identifier' => 'value',
                    ],
                    Config::DEFAULT_LABEL_FORMAT => [
                        'title' => $this->module->l('Default label format'),
                        'desc' => $this->module->l('Used when printing labels in order page.'),
                        'type' => 'select',
                        'list' => $labelPositionService->getLabelFormatList(),
                        'identifier' => 'value',
                    ],
                    Config::DEFAULT_LABEL_POSITION => [
                        'title' => $this->module->l('Default label position'),
                        'desc' => $this->module->l('Used when printing labels in order page.'),
                        'type' => 'select',
                        'list' => $labelPositionService->getLabelPositionList(),
                        'identifier' => 'value',
                        'form_group_class' => 'DPD_DEFAULT_LABEL_POSITION'
                    ],
                    Config::SEND_EMAIL_ON_PARCEL_CREATION => [
                        'title' => $this->module->l('Email on shipment creation'),
                        'hint' => $this->module->l('Send email with tracking information to customer when label is generated'),
                        'type' => 'bool',
                        'validation' => 'isBool',
                        'cast' => 'intval'
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save'),
                ],
            ],
            'cod_payment_configuration' => [
                'title' => $this->module->l('COD Payment configuration'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::ON_BOARD_INFO => [
                        'type' => 'free',
                        'desc' => $infoBlockRender->getInfoBlockTemplate($infoBlockText),
                        'class' => 'hidden',
                        'form_group_class' => 'dpd-info-block',
                    ],
                    Config::COD_PAYMENT_SWAP => [
                        'title' => $this->module->l('Payment methods'),
                        'type' => 'swap',
                        'class' => 'cod-payments-container',
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save'),
                ],
            ],
        ];
    }

    private function getPaymentMethods()
    {
        $paymentMethods = (array)PaymentModule::getInstalledPaymentModules();

        foreach ($paymentMethods as $key => $item) {
            $idModule = $item['id_module'];
            $module = Module::getInstanceById($idModule);

            if ($module) {
                $paymentMethods[$key]['display_name'] = $module->displayName;
            } else {
                unset($paymentMethods[$key]);
            }
        }

        return $paymentMethods;
    }
}
