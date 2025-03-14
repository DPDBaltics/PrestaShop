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
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\Service\LogsService;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsRequestSupportController extends AbstractAdminController
{
    public function postProcess()
    {
        parent::postProcess();

        $this->postProcessDownloadLogs();
    }

    private function postProcessDownloadLogs()
    {
        if (Tools::isSubmit('submitDpdDownloadLogs')) {
            /**
             * @var LogsService $logService
             */
            $logService = $this->module->getModuleContainer('invertus.dpdbaltics.service.logs_service');
            if (!$logService->downloadLogsCsv()) {
                $this->errors[] = $this->module->l('No logs to download.');
            }
        }
    }

    public function initContent()
    {
        parent::initContent();

        $tplVars = [
            'requestSupportData' => [
                'psVersion' => [
                    'name' => $this->module->l('PrestaShop Version'),
                    'value' => _PS_VERSION_,
                ],
                'phpVersion ' => [
                    'name' => $this->module->l('PHP Version'),
                    'value' => phpversion(),
                ],
                'mySqlVersion' => [
                    'name' => $this->module->l('MySQL Version'),
                    'value' => $this->getMysqlVersion(),
                ],
                'moduleVersion' => [
                    'name' => $this->module->l('DPD Module Version'),
                    'value' => $this->module->version,
                ],
                'dpdUsername' => [
                    'name' => $this->module->l('DPD Username'),
                    'value'=> Configuration::get(Config::WEB_SERVICE_USERNAME),
                ],
                'dateTime' => [
                    'name' => $this->module->l('Date & Time'),
                    'value' => date("D M d, Y G:i"),
                ],
            ],
            'isLogsOn' => Configuration::get(Config::TRACK_LOGS),
            'downloadLogsAction' => 'index.php?controller=' . ModuleTabs::ADMIN_REQUEST_SUPPORT_CONTROLLER . '&token=' . $this->token,
        ];

        $this->context->smarty->assign($tplVars);
        $this->context->smarty->assign([
            'content' => $this->context->smarty->fetch(
                $this->module->getLocalPath() . 'views/templates/admin/request-support.tpl'
            ),
        ]);
    }

    private function getMysqlVersion()
    {
        $query = 'SELECT version()';

        return pSQL(Db::getInstance()->getValue($query));
    }
}
