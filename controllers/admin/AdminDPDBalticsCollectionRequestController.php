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

use Invertus\dpdBaltics\Builder\Template\Admin\PhoneInputBuilder;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\Converter\FormDataConverter;
use Invertus\dpdBaltics\DTO\CollectionRequestData;
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\Repository\AddressRepository;
use Invertus\dpdBaltics\Repository\CollectionRequestRepository;
use Invertus\dpdBaltics\Repository\PhonePrefixRepository;
use Invertus\dpdBaltics\Service\API\CollectionRequestService;
use Invertus\dpdBaltics\Service\Exception\ExceptionService;
use Invertus\dpdBalticsApi\Exception\DPDBalticsAPIException;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsCollectionRequestController extends AbstractAdminController
{
    public function __construct()
    {
        $this->className = 'DPDCollectionRequest';
        $this->table = DPDCollectionRequest::$definition['table'];
        $this->identifier = DPDCollectionRequest::$definition['primary'];

        parent::__construct();

        $this->loadObject(true);
    }

    public function init()
    {
        $this->content .= $this->context->smarty->fetch($this->getTemplatePath() . 'admin-error-message.tpl');

        if (!$this->isXmlHttpRequest()) {
            $this->initList();
            $this->initForm();
        }

        parent::init();
    }

    private function initForm()
    {
        $this->loadObject(true);
        $dpdCountries = Country::getCountries($this->context->language->id, false);

        $dpdPickupCountryId = null;
        $dpdReceiverCountryId = null;

        $this->multiple_fieldsets = true;

        $date = new DateTime(isset($this->fields_value['shipment_date']) ? $this->fields_value['shipment_date'] : null);
        $this->fields_value['shipment_date'] = $date->format('Y-m-d');
        $this->fields_form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->module->l('Pick-up address'),
                    ],
                    'input' => [
                        [
                            'label' => $this->module->l('Prefill with selected address'),
                            'name' => 'pickup_address_prefill_placeholder',
                            'type' => 'free',
                        ],
                        [
                            'label' => $this->module->l('Full name/Company name'),
                            'name' => 'pickup_address_full_name',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('Mobile phone'),
                            'name' => 'pickup_address_mobile_phone',
                            'type' => 'free',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('Email address'),
                            'name' => 'pickup_address_email',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('Country'),
                            'name' => 'pickup_address_id_ws_country',
                            'type' => 'select',
                            'class' => 'fixed-width-xxl chosen',
                            'required' => true,
                            'options' => [
                                'id' => 'id_country',
                                'name' => 'name',
                                'query' => $dpdCountries,
                            ],
                        ],
                        [
                            'label' => $this->module->l('Zip code'),
                            'name' => 'pickup_address_zip_code',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('City/Region'),
                            'name' => 'pickup_address_city',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('Address'),
                            'name' => 'pickup_address_address',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                    ],
                ],
            ],
            [
                'form' => [
                    'legend' => [
                        'title' => $this->module->l('Receiver'),
                    ],
                    'input' => [
                        [
                            'label' => $this->module->l('Prefill with selected address'),
                            'name' => 'receiver_address_prefill_placeholder',
                            'type' => 'free',
                        ],
                        [
                            'label' => $this->module->l('Full name/Company name'),
                            'name' => 'receiver_address_full_name',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('Mobile phone'),
                            'name' => 'receiver_address_mobile_phone',
                            'type' => 'free',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('Email address'),
                            'name' => 'receiver_address_email',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('Country'),
                            'name' => 'receiver_address_id_ws_country',
                            'type' => 'select',
                            'class' => 'fixed-width-xxl chosen',
                            'required' => true,
                            'options' => [
                                'id' => 'id_country',
                                'name' => 'name',
                                'query' => $dpdCountries,
                            ],
                        ],
                        [
                            'label' => $this->module->l('Zip code'),
                            'name' => 'receiver_address_zip_code',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('City/Region'),
                            'name' => 'receiver_address_city',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                        [
                            'label' => $this->module->l('Address'),
                            'name' => 'receiver_address_address',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                        ],
                    ],
                ],
            ],
            [
                'form' => [
                    'legend' => [
                        'title' => $this->module->l('Shipment information'),
                    ],
                    'input' => [
                        [
                            'label' => $this->module->l('Enter the amount of parcels/pallets'),
                            'name' => 'info1',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                            'required' => true,
                            'desc' => $this->module->l('e.g. #1cll, 2pll (always starts with # and then the amount of parcels and/or pallets)')
                        ],
                        [
                            'label' => $this->module->l('Additional information (order number)'),
                            'name' => 'info2',
                            'type' => 'text',
                            'class' => 'fixed-width-xxl',
                        ],
                    ],
                ],
            ],
        ];
        /** @var PhonePrefixRepository $phonePrefixRepository */
        $phonePrefixRepository = $this->module->getModuleContainer('invertus.dpdbaltics.repository.phone_prefix_repository');

        /** @var PhoneInputBuilder $phoneInputBuilder */
        $phoneInputBuilder = $this->module->getModuleContainer('invertus.dpdbaltics.builder.template.admin.phone_input_builder');

        $saveBtnClasses = 'btn btn-default pull-right js-col-request-save';

        if (!$this->object->id) {
            $this->fields_form[2]['form']['submit'] = [
                'title' => $this->module->l('Save'),
                'class' => $saveBtnClasses,
            ];
        }

        $this->fields_value['pickup_address_prefill_placeholder'] = $this->renderPrefillSelect('pickup_address_');
        $this->fields_value['receiver_address_prefill_placeholder'] = $this->renderPrefillSelect('receiver_address_');

        if (Tools::getIsset('submitAdddpd_collection_request')) {
            $phoneData['pickup_address_mobile_phone_code'] = Tools::getValue('pickup_address_mobile_phone_code');
            $phoneData['pickup_address_mobile_phone'] = Tools::getValue('pickup_address_mobile_phone');

            $phoneData['receiver_address_mobile_phone_code'] = Tools::getValue('receiver_address_mobile_phone_code');
            $phoneData['receiver_address_mobile_phone'] = Tools::getValue('receiver_address_mobile_phone');
        } elseif (Tools::getIsset('id_dpd_collection_request')) {

            /** @var CollectionRequestRepository $collectionRequestRepository */
            $collectionRequestRepository = $this->module->getModuleContainer('invertus.dpdbaltics.repository.collection_request_repository');
            $phoneData = $collectionRequestRepository->getPhonesAndCodes(
                Tools::getValue('id_dpd_collection_request')
            );
        }

        if (!isset($phoneData) || !$phoneData) {
            $countryCalliungCode = null;

            $phoneData['pickup_address_mobile_phone_code'] = $countryCalliungCode;
            $phoneData['pickup_address_mobile_phone'] = null;
            $phoneData['receiver_address_mobile_phone_code'] = $countryCalliungCode;
            $phoneData['receiver_address_mobile_phone'] = null;
        }
        $phoneData['mobile_phone_code_list'] = $phonePrefixRepository->getCallPrefixes();
        $this->fields_value['pickup_address_mobile_phone'] = $phoneInputBuilder->renderPhoneInput(
            'pickup_address_mobile_phone',
            $phoneData['mobile_phone_code_list'],
            $phoneData['pickup_address_mobile_phone_code'],
            $phoneData['pickup_address_mobile_phone']
        );
        $this->fields_value['pickup_address_fix_phone'] = $phoneInputBuilder->renderPhoneInput(
            'pickup_address_fix_phone',
            $phoneData['mobile_phone_code_list']
        );

        $this->fields_value['receiver_address_mobile_phone'] = $phoneInputBuilder->renderPhoneInput(
            'receiver_address_mobile_phone',
            $phoneData['mobile_phone_code_list'],
            $phoneData['receiver_address_mobile_phone_code'],
            $phoneData['receiver_address_mobile_phone']
        );
        $this->fields_value['receiver_address_fix_phone'] = $phoneInputBuilder->renderPhoneInput(
            'receiver_address_fix_phone',
            $phoneData['mobile_phone_code_list']
        );
    }

    private function initList()
    {
        $this->addRowAction('viewCollectionRequest');

        $this->fields_list['id_dpd_collection_request'] = [
            'title' => $this->module->l('ID'),
            'type' => 'text',
        ];

        $this->fields_list['date_add'] = [
            'title' => $this->module->l('Shipment date'),
            'type' => 'date',
        ];

        $this->fields_list['pickup_address_contact_name'] = [
            'title' => $this->module->l('Pickup address contact name'),
            'type' => 'text',
        ];

        $this->fields_list['pickup_address_email'] = [
            'title' => $this->module->l('Pickup address email'),
            'type' => 'text',
        ];

        $this->fields_list['receiver_address_contact_name'] = [
            'title' => $this->module->l('Receiver address contact name'),
            'type' => 'text',
        ];

        $this->fields_list['receiver_address_email'] = [
            'title' => $this->module->l('Receiver address email'),
            'type' => 'text',
        ];
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        //checks if its add or update or submit
        if (Tools::isSubmit('adddpd_collection_request')
            || Tools::isSubmit('updatedpd_collection_request')
            || Tools::isSubmit('submitAdddpd_collection_request')) {
            $this->addJS($this->module->getLocalPath() . 'views/js/admin/zip_code_hint.js');
            $this->addJS($this->module->getLocalPath() . 'views/js/admin/collection_request.js');

            Media::addJsDef([
                'dpdAjaxUrl' =>
                    $this->context->link->getAdminLink(ModuleTabs::ADMIN_AJAX_CONTROLLER),
            ]);
        }

        $this->addCSS($this->module->getPathUri() . 'views/css/admin/collection_request.css');
    }

    private function renderPrefillSelect($prefix)
    {
        /** @var AddressRepository $addressRepository */
        $addressRepository = $this->module->getModuleContainer('invertus.dpdbaltics.repository.address_repository');
        $addresses = $addressRepository->findAllByShop();

        $this->context->smarty->assign('address_templates', $addresses);
        $this->context->smarty->assign('prefix', $prefix);

        return $this->context->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/admin/partials/address-select.tpl'
        );
    }

    //todo:: if Exception is caught it returns to list.
    public function postProcess()
    {
        if (Tools::isSubmit('submitAdddpd_collection_request')) {
            /** @var FormDataConverter $formDataConverter */
            $formDataConverter = $this->module->getModuleContainer('invertus.dpdbaltics.converter.form_data_converter');
            $data = Tools::getAllValues();

            /** @var CollectionRequestData $collectionRequestObj */
            $collectionRequestObj = $formDataConverter->convertCollectionRequestFormDataToCollectionRequestObj($data);

            /** @var CollectionRequestService $collectionRequestService */
            $collectionRequestService = $this->module->getModuleContainer('invertus.dpdbaltics.service.api.collection_request_service');
            try {
                $response = $collectionRequestService->createCollectionRequest($collectionRequestObj);
            } catch (DPDBalticsAPIException $e) {
                /** @var ExceptionService $exceptionService */
                $exceptionService = $this->module->getModuleContainer('invertus.dpdbaltics.service.exception.exception_service');
                $this->errors[] = $exceptionService->getErrorMessageForException(
                    $e,
                    $exceptionService->getAPIErrorMessages()
                );
                return;
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                return;
            }
            if ($response['status']) {
                $this->confirmations[] = $response['message'];
            } else {
                $this->errors[] = $response['message'];
            }
        }

        parent::postProcess();
    }

    public function displayViewCollectionRequestLink($token, $idCollectionRequest)
    {
        $collectionRequestUrl =
            $this->context->link->getAdminLink(ModuleTabs::ADMIN_COLLECTION_REQUEST_CONTROLLER, false);
        $collectionRequestUrl .=
            '&id_dpd_collection_request=' . $idCollectionRequest . '&updatedpd_collection_request&token=' . $token;

        $params = [
            'href' => $collectionRequestUrl,
            'action' => $this->module->l('View'),
            'icon' => 'icon-search-plus'
        ];

        return $this->renderListAction($params);
    }

    protected function renderListAction(array $params)
    {
        $this->context->smarty->assign($params);

        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/list-action.tpl');
    }
}
