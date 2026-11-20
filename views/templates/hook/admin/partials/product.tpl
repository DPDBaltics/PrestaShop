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
{foreach $products as $product}
<tr class="js-product">
    <td data-content="{l s='ID' mod='dpdbaltics'}" class="text-center js-product-id" scope="row">{$product.id_product|escape:'html':'UTF-8':false}</td>
    <td data-content="{l s='Product' mod='dpdbaltics'}" class="text-center js-product-name">{$product.name|escape:'html':'UTF-8':false}</td>
    {*todo: if weight by default is not in kg?*}
    <td data-content="{l s='Weight (kg)' mod='dpdbaltics'}" class="text-center js-product-weight">{$product.weight|escape:'html':'UTF-8':false}</td>
    <td data-content="{l s='Parcel' mod='dpdbaltics'}" class="text-center js-product-parcel-number">1</td>
    <td data-content="{l s='Quantity' mod='dpdbaltics'}" class="text-center js-product-quantity">{$product.quantity|escape:'html':'UTF-8':false}</td>
    <td data-content="{l s='Action' mod='dpdbaltics'}" class="text-center js-product-actions product-actions">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group form-group-sm">
                    <label class="control-label col-lg-12 text-center">
                        {l s='Move Quantity' mod='dpdbaltics'}
                    </label>
                    <div class="col-lg-12">
                        <input class="form-control js-product-qty-input"
                               type="number"
                               min="0"
                               title="{l s='Quantity' mod='dpdbaltics'}"
                        >
                        <span class="tooltips js-tooltips">{l s='Too many items. Max %count% product(s) can be moved' mod='dpdbaltics'}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group form-group-sm">
                    <label class="control-label col-lg-12 text-center">
                        {l s='Shipment' mod='dpdbaltics'}
                    </label>
                    <div class="col-lg-12">
                        <select title="{l s='Shipment' mod='dpdbaltics'}" class="js-shipment-select">
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group form-group-sm">
                    <label class="control-label col-lg-12 text-center">
                        {l s='Parcel' mod='dpdbaltics'}
                    </label>
                    <div class="col-lg-12">
                        <select title="{l s='Parcel' mod='dpdbaltics'}" class="js-parcel-select">
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </td>
    <td class="text-center product-actions">
        <button type="button" class="btn btn-default js-move-product {if $testOrder && !$testMode}disabled{/if}">{l s='Move' mod='dpdbaltics'}</button>
        <div id="deleteParcelBtnTemplate" class="hidden d-none">
            <button type="button" class="btn btn-default js-parcel-delete-btn">
                {l s='Delete' mod='dpdbaltics'}
            </button>
        </div>
    </td>
</tr>
{/foreach}
