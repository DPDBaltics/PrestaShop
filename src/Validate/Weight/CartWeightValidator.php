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


namespace Invertus\dpdBaltics\Validate\Weight;

use Invertus\dpdBaltics\Config\Config;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartWeightValidator
{
    const DISTRIBUTION_NONE = 'none';
    const DISTRIBUTION_PARCEL_PRODUCT = 'parcel_product';
    const DISTRIBUTION_PARCEL_QUANTITY = 'parcel_quantity';

    public function validate($cart, $parcelDistribution, $maxAllowedWeight)
    {
        if (!$maxAllowedWeight) {
            return true;
        }

        switch ($parcelDistribution) {
            case self::DISTRIBUTION_NONE:
                return $maxAllowedWeight >= $cart->getTotalWeight();

            case self::DISTRIBUTION_PARCEL_QUANTITY:
                return $this->isProductQuantityDistributionWeightValid($cart, $maxAllowedWeight);

            case self::DISTRIBUTION_PARCEL_PRODUCT:
                return $this->isProductDistributionWeightValid($cart, $maxAllowedWeight);

        }

        return false;
    }

    public function isEachProductWithinWeightLimit($cart, $maxProductWeight)
    {
        if ((float) $maxProductWeight <= 0) {
            return true;
        }

        foreach ($cart->getProducts() as $product) {
            if (!$this->isProductWeightWithinLimit($product['weight'], $maxProductWeight)) {
                return false;
            }
        }

        return true;
    }

    public function isProductWeightWithinLimit($productWeight, $maxProductWeight)
    {
        if ((float) $maxProductWeight <= 0) {
            return true;
        }

        return (float) $productWeight <= (float) $maxProductWeight;
    }

    private function isProductQuantityDistributionWeightValid($cart, $maxAllowedWeight)
    {
        $cartProducts = $cart->getProducts();
        $isValid = true;

        if (!$cartProducts) {
            return false;
        }

        foreach ($cartProducts as $product) {
            if ((int) $product['weight'] > 0) {
                $isValid = $maxAllowedWeight >= $product['weight'];

            }

            if (!$isValid) {
                break;
            }
        }

        return $isValid;
    }

    private function isProductDistributionWeightValid($cart, $maxAllowedWeight)
    {
        $cartProducts = $cart->getProducts();
        $isValid = true;

        foreach ($cartProducts as $product) {
            if ((int) $product['weight'] > 0) {
                $weight = (int) $product['weight'] * (int) $product['quantity'];
                $isValid = $maxAllowedWeight >= $weight;
            }

            if (!$isValid) {
                break;
            }
        }

        return $isValid;
    }
}
