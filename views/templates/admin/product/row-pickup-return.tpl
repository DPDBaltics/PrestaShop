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

<form id="product-service-form-{$idProduct|escape:'html':'UTF-8':false}" class="js-product-form js-product-pickup-return">
    <div class="row custom-product-row {if $isOddRow == true}odd-row{else}even-row{/if}">
        <input type="hidden" name="id-product" value="{$idProduct|escape:'html':'UTF-8':false}" disabled>
        <div class="col-xs-10">
            <h4>{$productName|escape:'html':'UTF-8':false}</h4>
        </div>

        <div class="col-xs-1">
            <span class="switch prestashop-switch">
                <input type="radio"
                       name="product-active-{$idProduct|escape:'html':'UTF-8':false}"
                       id="product-active-{$idProduct|escape:'html':'UTF-8':false}-on"
                       value="1"
                       disabled
                       {if $isActive}checked="checked"{/if}
                >
                <label for="product-active-{$idProduct|escape:'html':'UTF-8':false}-on" class="radioCheck">Yes</label>
                <input type="radio"
                       name="product-active-{$idProduct|escape:'html':'UTF-8':false}"
                       id="product-active-{$idProduct|escape:'html':'UTF-8':false}-off"
                       value="0"
                       disabled
                       {if !$isActive}checked="checked"{/if}
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
