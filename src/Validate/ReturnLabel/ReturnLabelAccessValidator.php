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

namespace Invertus\dpdBaltics\Validate\ReturnLabel;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ReturnLabelAccessValidator
{
    /**
     * @param int $idCustomer logged in customer, 0 for visitors and guests
     * @param int $idOrderCustomer customer who placed the order
     *
     * @return bool
     */
    public function isOwner($idCustomer, $idOrderCustomer)
    {
        if ((int) $idCustomer <= 0) {
            return false;
        }

        return (int) $idCustomer === (int) $idOrderCustomer;
    }

    /**
     * @param bool $isReturnServiceEnabled
     * @param bool $isEstonia
     * @param bool $isOrderDelivered
     * @param bool $hasDpdShipment
     *
     * @return bool
     */
    public function isReturnAvailable($isReturnServiceEnabled, $isEstonia, $isOrderDelivered, $hasDpdShipment)
    {
        return $isReturnServiceEnabled && !$isEstonia && $isOrderDelivered && $hasDpdShipment;
    }
}
