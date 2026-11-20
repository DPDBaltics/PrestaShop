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


namespace Invertus\dpdBaltics\Provider;

use Address;
use Carrier;
use Configuration;
use Currency;
use DPDBaltics;
use DPDPriceRule;
use DPDZone;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Repository\PriceRuleRepository;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Repository\ZoneRepository;
use Invertus\dpdBaltics\Validate\Weight\CartWeightValidator;
use Invertus\dpdBaltics\Verification\IsAddressInZone;
use Product;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductShippingCostProvider
{
    /**
     * @var DPDBaltics
     */
    private $module;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ZoneRepository
     */
    private $zoneRepository;

    /**
     * @var PriceRuleRepository
     */
    private $priceRuleRepository;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var CartWeightValidator
     */
    private $cartWeightValidator;

    public function __construct(
        DPDBaltics $module,
        ProductRepository $productRepository,
        ZoneRepository $zoneRepository,
        PriceRuleRepository $priceRuleRepository,
        Currency $currency,
        CartWeightValidator $cartWeightValidator
    ) {
        $this->module = $module;
        $this->productRepository = $productRepository;
        $this->zoneRepository = $zoneRepository;
        $this->priceRuleRepository = $priceRuleRepository;
        $this->currency = $currency;
        $this->cartWeightValidator = $cartWeightValidator;
    }

    public function getProductShippingCost($carrierReference, $idAddress)
    {
        // This method is still called when module is disabled so we need to do a manual check here
        if (!$this->module->active) {
            return false;
        }

        $carrier = Carrier::getCarrierByReference($carrierReference);

        $deliveryAddress = new Address($idAddress);

        $serviceCarrier = $this->productRepository->findProductByCarrierReference($carrier->id_reference);

        if ((bool) $serviceCarrier['is_home_collection']) {
            return false;
        }

        if ($idAddress && empty($this->zoneRepository->findZoneInRangeByAddress($deliveryAddress))) {
            return false;
        }

        // Get all price rules for current carrier
        $priceRulesIds = $this->priceRuleRepository->getByCarrierReference($deliveryAddress, $carrier->id_reference);

        $currentProduct = new Product(Tools::getValue('id_product'));
        $price = $currentProduct->price;
        $currency = $this->currency->id;
        $weight = $currentProduct->weight;

        if (!$this->cartWeightValidator->isProductWeightWithinLimit($weight, Configuration::get(Config::MAX_PRODUCT_WEIGHT))) {
            return false;
        }

        if ($idAddress) {
            foreach ($priceRulesIds as $priceRuleId) {
                $priceRule = new DPDPriceRule($priceRuleId, null);

                // Check if price rule is applicable for this cart
                if ($priceRule->isApplicableForProduct($price, $currency, $weight, $idAddress)) {
                    // If it's applicable - use price rule's price and don't check other price rules
                    return Tools::convertPrice($priceRule->price, $currency);
                }
            }
        } else {
            $priceRulesPriceArray = [];

            foreach ($priceRulesIds as $priceRuleId) {
                $priceRule = new DPDPriceRule($priceRuleId, null);

                $priceRulesPriceArray[] = (float) $priceRule->price;
            }

            return !empty($priceRulesPriceArray) ? min($priceRulesPriceArray) : $priceRulesPriceArray;
        }

        return false;
    }
}
