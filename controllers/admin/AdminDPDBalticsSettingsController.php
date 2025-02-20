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

use Invertus\dpdBaltics\Builder\Template\Admin\InfoBlockBuilder;
use Invertus\dpdBaltics\Collection\DPDProductInstallCollection;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\DTO\DPDProductInstall;
use Invertus\dpdBaltics\Exception\DpdCarrierException;
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Service\Carrier\CreateCarrierService;
use Invertus\dpdBaltics\Service\Carrier\CarrierUpdateHandler;
use Invertus\dpdBaltics\Service\Carrier\PrestashopCarrierRegenerationHandler;
use Invertus\dpdBaltics\Service\Carrier\UpdateCarrierService;
use Invertus\dpdBaltics\Service\LogsService;
use Invertus\dpdBaltics\Service\Product\ProductService;
use Invertus\dpdBaltics\Templating\InfoBlockRender;
use Invertus\dpdBaltics\Validate\Version\ModuleLatestVersionValidator;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsSettingsController extends AbstractAdminController
{
    private const FILE_NAME = 'AdminDPDBalticsSettingsController';

    public function init()
    {
        $parentReturn = parent::init();
        $this->initOptions();
        $this->bootstrap = true;

        if (!Tools::isSubmit('submitOptionsconfiguration')) {
            return $parentReturn;
        }
        parent::init();
    }

    protected function initOptions()
    {
        /** @var InfoBlockRender $infoBlockRender */
        $infoBlockRender = $this->module->getModuleContainer()->get('invertus.dpdbaltics.templating.info_block_render');
        $infoBlockText = $this->module->l('Here you can restart DPD on-board feature.');
        $blockRegenerateCarrierText = $this->module->l('Here you can regenerate prestashop carriers if they were accidentally deleted in back office or carriers are not visible in front end of the shop');

        $this->context->smarty->assign('googleMapsApiKeyLink', Config::GOOGLE_MAPS_API_KEY_LINK);

        $this->fields_options = [
            'web_service_configuration' => [
                'title' => $this->l('Web service configuration'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::SHIPMENT_TEST_MODE => [
                        'title' => $this->l('Shipment test mode'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'type' => 'bool',
                    ],
                    Config::WEB_SERVICE_USERNAME => [
                        'title' => $this->l('Username'),
                        'type' => 'text',
                        'validation' => 'isGenericName',
                        'class' => 'fixed-width-xl',
                    ],
                    Config::WEB_SERVICE_PASSWORD => [
                        'title' => $this->l('Password'),
                        'type' => 'text',
                        'class' => 'fixed-width-xl',
                        'auto_value' => false,
                    ],
                    Config::WEB_SERVICE_COUNTRY => [
                        'title' => $this->l('Country'),
                        'type' => 'radio',
                        'required' => true,
                        'choices' => [
                            Config::ESTONIA_ISO_CODE => $this->l('Estonia'),
                            Config::LATVIA_ISO_CODE => $this->l('Latvia'),
                            Config::LITHUANIA_ISO_CODE => $this->l('Lithuania'),
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'id' => 'submitDpdConnection'
                ],
            ],
            'product_page_configuration' => [
                'title' => $this->l('Product page configuration'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::SHOW_CARRIERS_IN_PRODUCT_PAGE => [
                        'title' => $this->l('Show carrier options in product page'),
                        'type' => 'bool',
                        'validation' => 'isBool',
                        'cast' => 'intval'
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
            'orders_page_configuration' => [
                'title' => $this->l('Orders listing page configuration', self::FILE_NAME),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::HIDE_ORDERS_LABEL_PRINT_BUTTON => [
                        'title' => $this->l('Hide print button in admin orders listing page'),
                        'type' => 'bool',
                        'validation' => 'isBool',
                        'cast' => 'intval'
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
            'log_configuration' => [
                'title' => $this->l('Log configuration'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::TRACK_LOGS => [
                        'title' => $this->l('Track logs'),
                        'type' => 'bool',
                        'validation' => 'isBool',
                        'cast' => 'intval'
                    ],
                ],
                'buttons' => [
                    'dpd_delete_logs' => [
                        'title' => $this->l('Delete logs'),
                        'icon' => 'process-icon-delete',
                        'class' => 'btn btn-default pull-left',
                        'name' => 'submitDpdDeleteLogs',
                        'type' => 'submit',
                    ],
                    'dpd_download_logs' => [
                        'title' => $this->l('Download logs'),
                        'icon' => 'process-icon-download',
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitDpdDownloadLogs',
                        'type' => 'submit',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
            'dpd_on_board' => [
                'title' => $this->l('DPD on-board'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::ON_BOARD_INFO => [
                        'type' => 'free',
                        'desc' => $infoBlockRender->getInfoBlockTemplate($infoBlockText),
                        'class' => 'hidden',
                        'form_group_class' => 'dpd-info-block',
                    ],
                ],
                'buttons' => [
                    'dpd_restart_on_board' => [
                        'title' => $this->l('Restart DPD on-board'),
                        'icon' => 'process-icon-refresh',
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitRestartOnBoard',
                        'type' => 'submit',
                    ],
                ],
            ],
            'dpd_carrier_regeneration' => [
                'title' => $this->l('DPD carrier rengeneration'),
                'icon' => 'dpd-icon-settings',
                'fields' => [
                    Config::PRESTASHOP_DPD_CARRIER_REGENERATE => [
                        'type' => 'free',
                        'desc' => $infoBlockRender->getInfoBlockTemplate($blockRegenerateCarrierText),
                        'class' => 'hidden',
                        'form_group_class' => 'dpd-info-block',
                    ],
                ],
                'buttons' => [
                    'dpd_regenerate_carriers' => [
                        'title' => $this->l('REGENERATE'),
                        'icon' => 'process-icon-refresh',
                        'class' => 'btn btn-default pull-right',
                        'name' => 'submitRegenerateCarriers',
                        'type' => 'submit',
                    ],
                ],
            ],
        ];
    }

    public function postProcess()
    {
        /** @var ModuleLatestVersionValidator $moduleVersionValidator */
        $moduleVersionValidator = $this->module->getModuleContainer()->get('invertus.dpdbaltics.validator.module_latest_version_validator');

        try {
            $isModuleVersionLatest = $moduleVersionValidator->validate();
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        if (!$isModuleVersionLatest) {
            $this->errors[] = $this->l('Please upgrade DPD Baltics module');
        }

        if (Tools::isSubmit('submitOptionsconfiguration')) {
            /** @var ProductService $productService */
            $productService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.product.product_service');
            /** @var CarrierUpdateHandler $carrierService */
            $carrierService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.carrier.carrier_update_handler');

            $newCountry = Tools::getValue(Config::WEB_SERVICE_COUNTRY);
            Configuration::updateValue(Config::DPD_PARCEL_IMPORT_COUNTRY_SELECTOR, Country::getByIso($newCountry));
            $productService->updateCarriersOnCountryChange($newCountry);
            $carrierService->updateCarrierName($newCountry);
        }

        $parentReturn = parent::postProcess();
        if (Tools::isSubmit('submitDpdDownloadLogs')) {
            $this->downloadLogs();
        }

        if (Tools::isSubmit('submitRestartOnBoard')) {
            $this->restartOnBoard();
        }

        if (Tools::isSubmit('submitRegenerateCarriers')) {
            /** @var  PrestashopCarrierRegenerationHandler $regenerationHandler */
            $regenerationHandler = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.carrier.prestashop_carrier_regeneration_handler');
            /** @var  Invertus\dpdBaltics\Logger\Logger $logger */
            $logger = $this->module->getModuleContainer()->get('invertus.dpdbaltics.logger.logger');
            try {
                $regenerationHandler->handle();
                $this->confirmations[] = $this->l('Prestashop carriers regenerated successfully');

            } catch (DpdCarrierException $e) {
                $logger->error($e->getMessage());
                $this->errors[] = $this->l('Could not regenerate carriers, please refer to module logs for more information');
            } catch (PrestaShopDatabaseException $e) {
                $logger->error($e->getMessage());
                $this->errors[] = $this->l('Could not regenerate carriers, please refer to module logs for more information');
            } catch (PrestaShopException $e) {
                $logger->error($e->getMessage());
                $this->errors[] = $this->l('Could not regenerate carriers, please refer to module logs for more information');
            }
        }

        if (Tools::strlen(Configuration::get(Config::WEB_SERVICE_PASSWORD)) > 0) {
            $passwordPlaceholder = $this->getPasswordPlaceholder();
        } else {
            $passwordPlaceholder = '';
        }

        $this->fields_options['web_service_configuration']['fields'][Config::WEB_SERVICE_PASSWORD]['value'] =
            $passwordPlaceholder;

        return $parentReturn;
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS($this->getModuleJSUri() . 'settings_password.js');
    }

    private function downloadLogs()
    {
        /**
         * @var LogsService $logService
         */
        $logService = $this->module->getModuleContainer('invertus.dpdbaltics.service.logs_service');
        if (!$logService->downloadLogsCsv()) {
            $this->errors[] = $this->l('No logs to download.');
        }
    }

    private function restartOnBoard()
    {
        Configuration::updateValue(Config::ON_BOARD_TURNED_ON, 1);
        Configuration::updateValue(Config::ON_BOARD_PAUSE, 0);
        Configuration::updateValue(Config::ON_BOARD_STEP, Config::STEP_MAIN_1);
        Configuration::updateValue(Config::ON_BOARD_MANUAL_CONFIG_CURRENT_PART, 1);

        Tools::redirectAdmin($this->context->link->getAdminLink(ModuleTabs::ADMIN_SETTINGS_CONTROLLER));
    }

    private function getPasswordPlaceholder()
    {
        return html_entity_decode(Config::WEB_SERVICE_PASSWORD_PLACEHOLDER);
    }

    /**
     * Encrypt password if it is submited & save
     *
     * @param string $plainPassword
     */
    public function updateOptionDpdWebServicePassword($plainPassword)
    {
        $passwordPlaceholder = $this->getPasswordPlaceholder();

        if (empty($plainPassword) || $passwordPlaceholder === $plainPassword) {
            return;
        }

        $encodedPassword = str_rot13($plainPassword);

        Configuration::updateValue(Config::WEB_SERVICE_PASSWORD, $encodedPassword, false, 0, 0);
    }
}
