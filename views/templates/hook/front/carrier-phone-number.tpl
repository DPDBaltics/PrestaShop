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
<div class="container dpd-checkout-phone-container dpd-phone-block{if $isAbove177} new-version {/if}">

    {if isset($currentController) && $currentController === 'supercheckout'}
        <div class="supercheckout-dpd-phone-error"></div>
        {else}
        {include file='module:dpdbaltics/views/templates/hook/front/partials/dpd-message.tpl' messageType='error'}
    {/if}
    <div id="phone-block-wrapper" class="row form-group">
        <div class="{if !($isOpcCheckout)}col-lg-5 col-12{/if}">
            <p class="form-control-label">{l s='This a phone number that will be used for deliveries' mod='dpdbaltics'}</p></div>
        <div class="{if !($isOpcCheckout)}col-lg-3 col-4 col-sm-12{/if} dpd-input-wrapper dpd-select-wrapper hasValue small-padding-sm-right css-dpd-phone-prefix">
            <select
                    class="chosen-select form-control-chosen"
                    name="dpd-phone-area">
                    {html_options options=$dpdPhoneArea selected=$contextPrefix}
            </select>
            <div class="control-label dpd-input-placeholder dpd-select-placeholder">
                {l s='Code' mod='dpdbaltics'}
            </div>
        </div>

        <div class="{if !($isOpcCheckout)}col-lg-4 col-8 col-sm-12{/if} dpd-input-wrapper{if isset($dpdPhone) && $dpdPhone} hasValue{/if} small-padding-sm-left">
            <input name="dpd-phone" id="dpd-carrier-{$carrierReference|escape:'html':'UTF-8':false}" type="text" class="form-control" {if isset($dpdPhone) && $dpdPhone}value="{$dpdPhone|escape:'html':'UTF-8':false}"{/if}>
            <div id="phone-input-placeholder" class="dpd-input-placeholder" for="dpd-phone">{l s='Phone' mod='dpdbaltics'}</div>
        </div>
    </div>
</div>
<hr class="phone-block-hr">

