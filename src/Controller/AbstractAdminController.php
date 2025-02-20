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


namespace Invertus\dpdBaltics\Controller;

use Configuration;
use DPDBaltics;
use Exception;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\OnBoard\Service\OnBoardStepActionService;
use Invertus\dpdBaltics\Provider\CurrentCountryProvider;
use Invertus\dpdBaltics\Provider\ImportExportURLProvider;
use Invertus\dpdBaltics\Repository\ParcelShopRepository;
use Invertus\dpdBaltics\Validate\Version\ModuleLatestVersionValidator;
use Media;
use ModuleAdminController;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}


class AbstractAdminController extends ModuleAdminController
{
    const FILENAME = 'DPDAbstractAdminController';

    private $flashMessageTypes = ['success', 'info', 'warning', 'error'];

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Add navigation to content
     */
    public function initContent()
    {
        if ($this->ajax) {
            return parent::initContent();
        }

        $this->displayTestModeWarning();
        $this->checkCanUpdateModule();
        $this->initFlashMessages();

        parent::initContent();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if (!Configuration::get(Config::ON_BOARD_TURNED_ON) && Configuration::get(Config::ON_BOARD_PAUSE)) {
            $this->page_header_toolbar_btn['on_board_pause'] = array(
                'href' => $this->context->link->getAdminLink(
                    Tools::getValue('controller'),
                    true,
                    [],
                    ['resumeOnBoard' => 1]
                ),
                'desc' => $this->module->l('Resume On-Board', self::FILENAME),
                'icon' => 'process-icon-toggle-off',
                'class' => 'onBoardResume'
            );
        }

        /** @var ImportExportURLProvider $importExportURLProvider */
        $importExportURLProvider = $this->module->getModuleContainer('invertus.dpdbaltics.provider.import_export_url_provider');
        $importExportUrl = $importExportURLProvider->getImportExportUrl();

        $this->page_header_toolbar_btn['logs'] = [
            'href' => $this->context->link->getAdminLink(ModuleTabs::ADMIN_LOGS_CONTROLLER),
            'desc' => $this->module->l('Logs', self::FILENAME),
            'icon' => 'process-icon-database',
        ];

        $this->page_header_toolbar_btn['import_export'] = [
            'href' => $importExportUrl,
            'desc' => $this->module->l('Import/Export', self::FILENAME),
            'icon' => 'process-icon-save-date',
        ];

        $this->page_header_toolbar_btn['request_support'] = [
            'href' => $this->context->link->getAdminLink(ModuleTabs::ADMIN_REQUEST_SUPPORT_CONTROLLER),
            'desc' => $this->module->l('Request support', self::FILENAME),
            'icon' => 'process-icon-newCombination toolbar-new',
        ];
    }

    public function postProcess()
    {
        if (Tools::isSubmit('resumeOnBoard')) {
            /** @var OnBoardStepActionService $onBoardActionService */
            $onBoardActionService = $this->module->getModuleContainer('invertus.dpdbaltics.on_board.service.on_board_step_action_service');
            $onBoardActionService->enableOnBoarding();
            $onBoardActionService->resumeOnBoarding();

            Tools::redirectAdmin($this->context->link->getAdminLink(Tools::getValue('controller')));
        }

        return parent::postProcess();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->getModuleCssUri().'admin-controllers.css');
        $this->addCSS($this->module->getPathUri() . 'views/css/admin/global_module.css');
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/admin/tab.js');

        if (Configuration::get(Config::ON_BOARD_TURNED_ON)) {
            // for draggable functionality
            $this->context->controller->addJqueryUI('ui.draggable');

            $this->context->controller->addJS($this->module->getPathUri() . 'views/js/admin/on-board.js');
            $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/admin/on-board.css');
            $jsVars['onBoard'] = array(
                'stopWarning' => $this->l('Stop DPD on-board?'),
                'pauseWarning' => $this->l('Pause DPD on-board?'),
                'ajaxUrl' => $this->context->link->getAdminLink(ModuleTabs::ADMIN_AJAX_ON_BOARD_CONTROLLER),
                'currentStep' => Configuration::get(Config::ON_BOARD_STEP)
            );

            Media::addJsDef($jsVars);
        }
    }

    /**
     * Get module JS Uri
     *
     * @return string
     */
    protected function getModuleJSUri()
    {
        return $this->module->getPathUri() . 'views/js/admin/';
    }

    protected function getModuleCssUri()
    {
        return $this->module->getPathUri() . 'views/css/admin/';
    }

    protected function getMessageWithLink($type, $link, $messageStart, $messageEnd)
    {
        if (!in_array($type, $this->flashMessageTypes)) {
            throw new Exception(sprintf(
                'Invalid flash message type "%s" supplied. Available types are: %s',
                $type,
                implode(',', $this->flashMessageTypes)
            ));
        }
        return $this->context->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/admin/message-with-link.tpl',
            [
                'type' => $type,
                'messageLink' => $link,
                'messageStart' => $messageStart,
                'messageEnd' => $messageEnd
            ]
        );
    }

    /**
     * Display test mode warning if test mode is enabled
     */
    private function displayTestModeWarning()
    {
        $isTestModeEnabled = (bool) Configuration::get(Config::SHIPMENT_TEST_MODE);
        if ($isTestModeEnabled) {
            $this->warnings[] = $this->module->l('Please note: module is in test mode', self::FILENAME);
        }
        $isSecretTestModeEnabled = (bool) Configuration::get(Config::SECRET_TEST_MODE);
        if ($isSecretTestModeEnabled) {
            $this->warnings[] = $this->module->l('Please note: secret test mode is enabled', self::FILENAME);
        }

        /** @var ParcelShopRepository $parcelShopRepo */
        $parcelShopRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.parcel_shop_repository');

        /** @var CurrentCountryProvider $currentCountryProvider */
        $currentCountryProvider = $this->module->getModuleContainer('invertus.dpdbaltics.provider.current_country_provider');

        $countryCode = $currentCountryProvider->getCurrentCountryIsoCode();

        if (!$parcelShopRepo->hasAnyParcelShops($countryCode)) {
            $this->warnings[] = $this->context->smarty->fetch(
                $this->module->getLocalPath() . 'views/templates/admin/warning-message-with-link.tpl',
                [
                    'messageLink' => $this->context->link->getAdminLink(ModuleTabs::ADMIN_IMPORT_EXPORT_CONTROLLER) . "#import-Parcels-button",
                    'messageStart' => $this->module->l('“Please note: Pick-up carrier is not available for your shop at the moment, because you don’t have pick-up points. To import pick-up points ', self::FILENAME),
                    'messageEnd' => '',
                    'linkText' => $this->module->l('click here', self::FILENAME)
                ]
            );
        }
    }

    /**
     * Display flash messages if there is any
     */
    private function initFlashMessages()
    {
        foreach ($this->flashMessageTypes as $type) {
            if (!isset($this->context->cookie->{$type})) {
                continue;
            }

            $messages = json_decode($this->context->cookie->{$type}, true);

            foreach ($messages as $message) {
                switch ($type) {
                    case 'success':
                        $this->confirmations[] = $message;
                        break;
                    case 'warning':
                        $this->warnings[] = $message;
                        break;
                    case 'error':
                        $this->errors[] = $message;
                        break;
                    case 'info':
                        $this->informations[] = $message;
                        break;
                }
            }

            unset($this->context->cookie->{$type});
        }
    }

    private function checkCanUpdateModule()
    {
        /** @var ModuleLatestVersionValidator $moduleVersionValidator */
        $moduleVersionValidator = $this->module->getModuleContainer()->get('invertus.dpdbaltics.validator.module_latest_version_validator');

        try {
            $isModuleVersionLatest = $moduleVersionValidator->validate();
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        if (!$isModuleVersionLatest) {
            $this->warnings[] = $this->context->smarty->fetch(
                $this->module->getLocalPath() . 'views/templates/admin/warning-message-with-link.tpl',
                [
                    'messageLink' => Config::DPD_GITHUB_REPO_RELEASE_LATEST_DOWNLOAD_URL,
                    'messageStart' => $this->module->l('You are using an outdated version of the module. Please update the module to the latest version. ', self::FILENAME),
                    'messageEnd' => '',
                    'linkText' => $this->module->l('Download latest module', self::FILENAME)
                ]
            );
        }
    }
}
