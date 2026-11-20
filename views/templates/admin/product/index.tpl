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

<div class="panel products-block">
    <div class="row product-header-row custom-product-row">
        <div class="col-xs-1">
            <p>{l s='Product' mod='dpdbaltics'}</p>
        </div>
        <div class="css-product-margin {if $isMultiShop}col-xs-1{else}col-xs-2{/if}">
            <p>{l s='Carrier name' mod='dpdbaltics'}</p>
        </div>
        <div class="css-product-margin {if $isMultiShop}col-xs-2{else}col-xs-3{/if} row">
            <p>{l s='Carrier delivery time' mod='dpdbaltics'}</p>
        </div>
        <div class="css-product-margin col-xs-2">
            <p>{l s='Zone' mod='dpdbaltics'}</p>
        </div>
        <div class="css-product-margin col-xs-1">
            <p>{l s='Availability' mod='dpdbaltics'}</p>
        </div>
        <div class="css-product-margin col-xs-2 {if !$isMultiShop}hidden d-none{/if}">
            <p>{l s='Shop' mod='dpdbaltics'}</p>
        </div>
        <div class="col-xs-1">
            <p>{l s='Active' mod='dpdbaltics'}</p>
        </div>

        <div class="col-xs-1">

        </div>
    </div>
    {foreach $rows as $row}
        {$row nofilter}
    {/foreach}
</div>
