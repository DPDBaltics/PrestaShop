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

<form id="product-home-collection-form-{$idProduct|escape:'html':'UTF-8':false}" class="js-product-form js-product-home-collection">
    <div class="row custom-product-row {if $isOddRow == true}odd-row{else}even-row{/if}">
        <input type="hidden" name="home-collection-product-id" value="{$idProduct|escape:'html':'UTF-8':false}" disabled>
        <div class="col-xs-8">
            <h4>{$productName|escape:'html':'UTF-8':false} {l s=' Home collection' mod='dpdbaltics'}</h4>
        </div>

        <div class="product-select-wrapper js-home-collection-select col-xs-2">
            <select class="chosen" name="id_service" disabled>
                {foreach $homeCollectionProducts as $homeCollectionProduct}
                    <option
                        value="{$homeCollectionProduct.id_dpd_service_carrier|escape:'html':'UTF-8':false}"
                        {if $activeHomeColService && $activeHomeColService == $homeCollectionProduct.id_dpd_service_carrier} selected="selected"{/if}
                    >
                        {$homeCollectionProduct.service_name|escape:'html':'UTF-8':false}
                    </option>
                {/foreach}
            </select>
        </div>

        <div class="col-xs-1">
            <span class="switch prestashop-switch">
                <input type="radio"
                       name="product-active-{$idProduct|escape:'html':'UTF-8':false}"
                       id="product-active-{$idProduct|escape:'html':'UTF-8':false}-on"
                       value="1"
                       disabled
                       {if $activeHomeColService}checked="checked"{/if}
                >
                <label for="product-active-{$idProduct|escape:'html':'UTF-8':false}-on" class="radioCheck">Yes</label>
                <input type="radio"
                       name="product-active-{$idProduct|escape:'html':'UTF-8':false}"
                       id="product-active-{$idProduct|escape:'html':'UTF-8':false}-off"
                       value="0"
                       disabled
                       {if !$activeHomeColService}checked="checked"{/if}
                >
                <label for="product-active-{$idProduct|escape:'html':'UTF-8':false}-off" class="radioCheck">No</label>
                <a class="slide-button btn"></a>
            </span>
        </div>
        <div class="col-xs-1">
            {include file='./buttons.tpl' dpd_product_id_identifier=$idProduct}
        </div>
    </div>
</form>
