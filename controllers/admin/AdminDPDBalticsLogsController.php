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

use Invertus\dpdBaltics\Controller\AbstractAdminController;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsLogsController extends AbstractAdminController
{
    public function __construct()
    {
        $this->className = 'DPDProduct';
        $this->table = DPDLog::$definition['table'];
        $this->identifier = DPDLog::$definition['primary'];
        $this->allow_export = true;

        parent::__construct();
        $this->initList();
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }


    private function initList()
    {
        $this->list_no_link = true;

        $this->fields_list = [
            'id_dpd_log' => [
                'title' => $this->module->l('ID'),
                'type' => 'text',
                'havingFilter' => true
            ],
            'request' => [
                'title' => $this->module->l('request'),
                'type' => 'text',
                'havingFilter' => true
            ],
            'response' => [
                'title' => $this->module->l('response'),
                'type' => 'text',
                'havingFilter' => true
            ],
            'status' => [
                'title' => $this->module->l('status'),
                'type' => 'text',
                'havingFilter' => true
            ],
            'date_add' => [
                'title' => $this->module->l('Created date'),
                'type' => 'datetime',
                'havingFilter' => true
            ]
        ];
    }
}
