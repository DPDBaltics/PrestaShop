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

namespace Invertus\dpdBaltics\Service;

use Address;
use Cart;
use Context;
use DPDBaltics;
use Invertus\dpdBaltics\Exception\DpdCarrierException;
use Invertus\dpdBaltics\ORM\EntityManager;
use Invertus\dpdBaltics\Repository\OrderRepository;
use Invertus\dpdBaltics\Repository\PhonePrefixRepository;
use Invertus\dpdBaltics\Config\Config;
use DPDOrderPhone;
use Invertus\dpdBaltics\Validate\Compatibility\OpcModuleCompatibilityValidator;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CarrierPhoneService
{
    const LITHUANIA_FULL_PHONE_PREFIX = '370';
    const LITHUANIA_PHONE_PREFIX = '86';
    const LITHUANIA_NEW_PHONE_PREFIX = '6';
    const LITHUANIA_PHONE_LENGTH = 9;

    /** @var dpdbaltics */
    private $module;

    /** @var Context */
    private $context;

    private $entityManager;
    /**
     * @var PhonePrefixRepository
     */
    private $phonePrefixRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var OpcModuleCompatibilityValidator
     */
    private $opcModuleCompatibilityValidator;

    public function __construct(
        DPDBaltics $module,
        Context $context,
        EntityManager $entityManager,
        PhonePrefixRepository $phonePrefixRepository,
        OrderRepository $orderRepository,
        OpcModuleCompatibilityValidator $opcModuleCompatibilityValidator
    ) {
        $this->module = $module;
        $this->context = $context;
        $this->entityManager = $entityManager;
        $this->phonePrefixRepository = $phonePrefixRepository;
        $this->orderRepository = $orderRepository;
        $this->opcModuleCompatibilityValidator = $opcModuleCompatibilityValidator;
    }

    public function getCarrierPhoneTemplate($cartId, $carrierReference = null)
    {
        $cart = new Cart($cartId);

        if (!\Validate::isLoadedObject($cart)) {
            throw new DpdCarrierException(
                'Could not load cart',
                Config::ERROR_COULD_NOT_LOAD_CART
            );
        }

        if (!$carrierReference) {
            $carrierReference = (new \Carrier($cart->id_carrier))->id_reference;
        }

        $phone = '';

        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->module->getModuleContainer()->get('invertus.dpdbaltics.repository.order_repository');

        $dpdOrderPhone = $orderRepository->getPhoneByIdCart($cart->id);

        if (!empty($dpdOrderPhone)) {
            $phone = $dpdOrderPhone['phone'];
        }

        if (empty($dpdOrderPhone)) {
            $address = new Address($cart->id_address_delivery);

            $phone = $address->phone ? $address->phone : $address->phone_mobile;
        }

        $phoneData['mobile_phone_code_list'] = $this->phonePrefixRepository->getCallPrefixesFrontOffice();

        $phonePrefix = $this->context->country->call_prefix;
        $this->context->controller->addJqueryPlugin('chosen');
        //Formats phone number, removes prefix when added on main field
        $phone = $this->removePhonePrefix($phone, $phonePrefix);
        $this->context->smarty->assign(
            [
                'carrierReference' => $carrierReference,
                'dpdPhone' => $phone,
                'dpdPhoneArea' => $phoneData['mobile_phone_code_list'],
                'contextPrefix' => Config::PHONE_CODE_PREFIX . $phonePrefix,
                'isAbove177' => Config::isPrestashopVersionAbove177(),
                'isOpcCheckout' => $this->opcModuleCompatibilityValidator->isOpcModuleInUse(),
            ]
        );

        return $this->context->smarty->fetch(
             'module:dpdbaltics/views/templates/hook/front/carrier-phone-number.tpl'
        );
    }

    public function saveCarrierPhone($idCart, $dpdPhone, $dpdPhoneCode)
    {
        $idDpdOrderPhone = $this->orderRepository->getOrderPhoneIdByCartId($idCart);

        if (!$idDpdOrderPhone) {
            $dpdOrderPhone = new DPDOrderPhone();
        } else {
            $dpdOrderPhone = new DPDOrderPhone($idDpdOrderPhone);
        }
        //Formats phone number, removes prefix when added on main field
        $dpdOrderPhone->phone = $this->removePhonePrefix($dpdPhone, $dpdPhoneCode);
        $dpdOrderPhone->phone_area = $dpdPhoneCode;
        $dpdOrderPhone->id_cart = $idCart;

        if (!$dpdOrderPhone->save()) {
           throw new DpdCarrierException(
               'Could not save phone number',
               Config::ERROR_COULD_NOT_SAVE_PHONE_NUMBER
           );
        }

        return true;
    }

    public function removePhonePrefix($phone, $phonePrefix)
    {
        $prefixLength = strlen($phonePrefix);
        if (strpos($phone, $phonePrefix) === 0) {
            return substr($phone, $prefixLength);
        }
        if (strpos($phone, Config::PHONE_CODE_PREFIX . $phonePrefix) === 0) {
            return substr($phone, $prefixLength + strlen(Config::PHONE_CODE_PREFIX));
        }
        if ($phonePrefix === $this::LITHUANIA_FULL_PHONE_PREFIX && strlen($phone) === $this::LITHUANIA_PHONE_LENGTH) {
            return $this::LITHUANIA_NEW_PHONE_PREFIX . substr($phone, strlen($this::LITHUANIA_PHONE_PREFIX));
        }

        return $phone;
    }

}
