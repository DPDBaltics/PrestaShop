{**
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
 *}

<div id="dpdTempates">
    <div id="pudoTemplate" {if !$is_pudo}class="hidden d-none"{/if}>
        {include file='./partials/pudo-address.tpl'}
    </div>
    <div id="shipmentTemplate">
        {include file="./partials/shipment.tpl"}
    </div>

    <div id="orderReturnTemplate">
        <div class="col-lg-12 return-link-holder">
{*            <a href="{$orderReturnLink|escape:'htmlall':'UTF-8'}" class="btn btn-default js-order-return-button">*}
{*                {l s='Initiate return' mod='dpdbaltics'}*}
{*            </a>*}
        </div>
    </div>
</div>