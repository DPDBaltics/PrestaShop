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

namespace Invertus\dpdBaltics\Service;

use Address;
use Cart;
use Invertus\dpdBaltics\Factory\ContextFactory;
use Invertus\dpdBaltics\Repository\PriceRuleRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShippingPriceCalculationService
{
    /**
     * @var PriceRuleService
     */
    private $priceRuleService;
    /**
     * @var PriceRuleRepository
     */
    private $priceRuleRepository;

    public function __construct(
        PriceRuleService $priceRuleService,
        PriceRuleRepository $priceRuleRepository
    ) {
        $this->priceRuleService = $priceRuleService;
        $this->priceRuleRepository = $priceRuleRepository;
    }

    /**
     * @param Cart $cart
     * @param Address $deliveryAddress
     *
     * @return float|false
     */
    public function calculate(Cart $cart, \Carrier $carrier, Address $deliveryAddress)
    {
        $shippingCosts = 0.0;

        $priceRulesIds = $this->priceRuleRepository->getByCarrierReference(
            $deliveryAddress,
            $carrier->id_reference,
            true
        );

        $result = $this->priceRuleService->applyPriceRuleForCarrier(
            $cart,
            $priceRulesIds,
            ContextFactory::getShop()->id
        );

        if (!$result) {
            return false;
        }

        $shippingCosts += $result;

        $shippingCosts += $this->applyAdditionalCosts($cart);

        return $shippingCosts;
    }
    /**
     * @param Cart $cart
     *
     * @return float
     */
    private function applyAdditionalCosts(Cart $cart)
    {
        $products = $cart->getProducts();
        $additionalShippingCosts = 0.0;
        foreach ($products as $product) {
            if ((float)$product['additional_shipping_cost']) {
                $additionalShippingCosts += (float)$product['additional_shipping_cost'];
            }
        }
        return $additionalShippingCosts;
    }
}
