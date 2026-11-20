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

declare(strict_types=1);

namespace Invertus\dpdBaltics\Infrastructure\Adapter;

use Invertus\dpdBaltics\Infrastructure\Utility\VersionUtility;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Adapter for Tools class compatibility across PrestaShop versions
 */
class ToolsAdapter
{
    /**
     * Format price with currency symbol - compatible across PrestaShop versions
     *
     * @param float $price
     * @param \Context|null $context
     * @return string
     */
    public static function displayPrice($price, $context = null)
    {
        $context = $context ?: \Invertus\dpdBaltics\Factory\ContextFactory::getContext();
        
        if (VersionUtility::isPsVersionGreaterOrEqualTo('9.0.0')) {
            // PrestaShop 9+ approach
            return $context->getCurrentLocale()->formatPrice(
                $price,
                $context->currency->iso_code
            );
        }
        
        // PrestaShop 8 and below approach
        return \Tools::displayPrice($price);
    }
}
