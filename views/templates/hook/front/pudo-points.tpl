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
{if (!isset($disableMap) || (isset($disableMap) && !$disableMap))}
    <div class=" {if $currentController == 'supercheckout'}supercheckout-pudo-container {/if}dpd-checkout-pickup-container dpd-pudo-container pickup-map-{$carrierId|escape:'html':'UTF-8':false}"
         data-id="{$carrierId|escape:'html':'UTF-8':false}" {if isset($dpdIdCarrier) && $dpdIdCarrier}data-pudo-id-carrier="{$dpdIdCarrier|escape:'html':'UTF-8':false}"{/if}>
        <div class="panel panel-default">
            {include file='module:dpdbaltics/views/templates/hook/front/partials/dpd-message.tpl' messageType='error'}
            <div class="col-lg-12 col-12 ">
                <p class="form-control-label"> {l s='Please select your pick up point' mod='dpdbaltics'}</p>
            </div>
            <div class="panel-body">
                {include file='module:dpdbaltics/views/templates/hook/front/partials/pudo-search-block.tpl'}
                <div class="clearfix">
                    &nbsp;
                </div>
                {if $pickUpMap}
                    <div id="dpd-pudo-map-{$carrierId|escape:'html':'UTF-8':false}"></div>
                {/if}
                <div class="points-container {if $show_shop_list === 'LIST'}dpd-hidden{/if}">
                    {include file='module:dpdbaltics/views/templates/hook/front/partials/markers-list.tpl' pudoServices=$pudoServices}
                </div>
            </div>

            {*holds map coordinates. Needed on async action*}
            <input class="dpd-hidden hidden" name="pudo-markers" value="">

            {*inidcated default coordinates if pudo map data is not available*}
            {if isset($coordinates.lat) && isset($coordinates.lng)}
                <input class="dpd-hidden hidden" name="default-lat" value="{$coordinates.lat|floatval}">
                <input class="dpd-hidden hidden" name="default-lng" value="{$coordinates.lng|floatval}">
            {/if}
        </div>
        <div class="clearfix"></div>
    </div>
{/if}
