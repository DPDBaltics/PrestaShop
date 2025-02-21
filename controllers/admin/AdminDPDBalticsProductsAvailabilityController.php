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
use Invertus\dpdBaltics\Exception\ProductAvailabilityUpdateException;
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\Provider\ProductAvailabilityProvider;
use Invertus\dpdBaltics\Service\Product\ProductAvailabilityService;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsProductsAvailabilityController extends AbstractAdminController
{
    public function __construct()
    {
        $this->className = 'DPDProduct';
        $this->table = DPDProduct::$definition['table'];
        $this->identifier = DPDProduct::$definition['primary'];
        $this->allow_export = true;
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

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        /** @var ProductAvailabilityProvider $productAvailabilityProvider */
        $productAvailabilityProvider = $this->module->getModuleContainer('invertus.dpdbaltics.provider.product_availability_provider');

        Media::addJsDef([
            'dpdbaltics' => [
                'messages' => [
                    'error' => [
                        'emptyAvailabilityTimeValue' => $this->module->l('Product availability time range cannot have empty values'),
                        'emptyProductAvailability' => $this->module->l('No product availability are configured'),
                    ],
                    'success' => [
                        'saved' => $this->module->l('Successfully saved'),
                    ],
                ],
                'notifications' => [
                    'saveProgress' => $this->module->l('Saving...'),
                ],
                'url' => [
                    'productAvailabilityControllerUrl' => $this->context->link->getAdminLink(ModuleTabs::ADMIN_PRODUCT_AVAILABILITY_CONTROLLER),
                    'productControllerUrl' => $this->context->link->getAdminLink(ModuleTabs::ADMIN_PRODUCTS_CONTROLLER),
                ],
                'entity' => [
                    'productAvailabilityRanges' => $productAvailabilityProvider->getProductAvailabilityForJS(Tools::getValue('id_dpd_product')),
                ],
            ],
        ]);

        $this->addJS($this->module->getPathUri().'views/js/admin/models/DPDProductAvailabilityRangesData.js');
        $this->addJS($this->module->getPathUri().'views/js/admin/product_availability.js');
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
        $this->_select = 'p.`day`, p.`interval_start`, p.`interval_end`';

        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'dpd_product_availability` p ON p.`product_reference`= a.`product_reference`';

        $this->_where = ' AND (a.`product_reference` = "' . Config::PRODUCT_TYPE_SAME_DAY_DELIVERY .
            '" OR a.`product_reference` = "' . Config::PRODUCT_TYPE_SATURDAY_DELIVERY . '")';

        $this->_group = 'GROUP BY a.product_reference';
        parent::getList($idLlang, $orderBy, $orderWay, $start, $limit, $idLangShop);
    }

    /**
     * Render form with prefilled values
     *
     * @return string
     */
    public function renderForm()
    {
        $deliveryDays = [
            $this->module->l('Monday'),
            $this->module->l('Tuesday'),
            $this->module->l('Wednesday'),
            $this->module->l('Thursday'),
            $this->module->l('Friday'),
        ];

        $this->context->smarty->assign([
            'daysList' => $deliveryDays,
            'productId' => Tools::getValue('id_dpd_product')
            ]
        );

        $this->fields_value['id_dpd_product'] = (int) Tools::getValue('id_dpd_product');
        $this->fields_value['ranges'] = $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/product_availability.tpl'
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

        if (!Tools::getValue('buttonName') === 'processSaveProductAvailabilities' ||
            !Tools::getValue('buttonName') === 'processSaveAndStayProductAvailabilities') {
            return true;
        }
        $response['status'] = 1;

        $productId = Tools::getValue('id_dpd_product');
        $timeRanges = Tools::getValue('time_ranges');

        $product = new DPDProduct($productId);
        $response['status'] = 1;

        /** @var $productAvailabilityService ProductAvailabilityService */
        $productAvailabilityService = $this->module->getModuleContainer('invertus.dpdbaltics.service.product.product_availability_service');

        try {
            $productAvailabilityService->updateProductAvailabilities($product->product_reference, $timeRanges);
            $response['id_dpd_product'] = $product->id_dpd_product;
        } catch (ProductAvailabilityUpdateException $e) {
            $response['status'] = 0;
            $response['error'] = $e->getMessage();
        } catch (Exception $e) {
            $response['status'] = 0;
            $response['error'] = $e->getMessage();
        }

        $this->ajaxRender(json_encode($response));
        exit;
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
            'product_reference' => [
                'title' => $this->module->l('Product reference'),
                'type' => 'text',
                'align' => 'center',
            ],
            'id_dpd_product' => [
                'title' => $this->module->l('Product id'),
                'type' => 'text',
                'align' => 'center',
            ]
        ];
    }

    /**
     * Form definition
     */
    private function initForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Product availability'),
            ],
            'input' => [
                [
                    'label' => '',
                    'type' => 'free',
                    'name' => 'alerts',
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
                    'name' => 'processSaveProductAvailabilities',
                    'type' => 'button',
                    'class' => 'pull-right',
                ],
                [
                    'title' => $this->module->l('Save and stay'),
                    'icon' => 'process-icon-save',
                    'name' => 'processSaveAndStayProductAvailabilities',
                    'type' => 'button',
                    'class' => 'pull-right',
                ],
            ],
        ];
    }
}
