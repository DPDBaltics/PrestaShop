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

<div class="row">
    <div class="col-lg-12">
        <div class="panel card" id="dpd-order-panel">
            <div class="panel-heading card-header">
                <img src="{$dpdLogoUrl|escape:'htmlall':'UTF-8'}">

                {l s='dpdbaltics shipping' mod='dpdbaltics'}
                <span class="badge">
                    <a class="dpd-extra-expand form-hidden">
                        {l s='[expand]' mod='dpdbaltics'}
                    </a>
                </span>
                {if $testOrder}
                    <span class="badge">
                        {l s='TEST ORDER' mod='dpdbaltics'}
                    </span>
                {/if}
            </div>
            {if $shipment->return_pl_number}
                <div class="alert alert-warning" role="alert">
                    {l s='Return label has been created by customer!' mod='dpdbaltics'}
                </div>
            {/if}
            <div class="panel-body card-body">
                {if !empty($shipment)}
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th class="text-center">{l s='Shipment' mod='dpdbaltics'}</th>
                                <th class="text-center">{l s='Parcels' mod='dpdbaltics'}</th>
                                <th class="text-center">{l s='Unique products' mod='dpdbaltics'}</th>
                                <th class="text-center">{l s='Label printed' mod='dpdbaltics'}</th>
                                <th></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="text-center">{l s='id: ' mod='dpdbaltics'} {$shipment->id|escape:'html':'UTF-8':false}</td>
                                <td class="text-center">{$shipment->num_of_parcels|intval}</td>
                                <td class="text-center">{$total_products|intval}</td>
                                <td class="text-center">{if $shipment->printed_label}{l s='Yes' mod='dpdbaltics'}{else}{l s='No' mod='dpdbaltics'}{/if}</td>
                                <td>
                                    <a href="#"
                                       class="btn btn-default pull-right js-print-label-btn"
                                       style="display: none"
                                       data-action="print"
                                       data-shipment-id={$shipment->id|escape:'html':'UTF-8':false}
                                    >
                                        <i class="process-icon-save"></i>
                                        {if 'download' == $printLabelOption}
                                            {l s='Download label' mod='dpdbaltics'}
                                        {else}
                                            {l s='Print label' mod='dpdbaltics'}
                                        {/if}
                                    </a>
                                </td>
                                {if !$isAutomated}
                                <td>
                                    <a href="{$adminLabelLink|escape:'html':'UTF-8':false}&shipment_id={$shipment->id|escape:'html':'UTF-8':false}"
                                       class="btn btn-default pull-right pull-right"
                                    >
                                        <i class="process-icon-save"></i>
                                        {l s='Download return label' mod='dpdbaltics'}
                                    </a>
                                </td>
                                {/if}
                            </tr>
                            </tbody>
                        </table>
                    </div>
                {/if}
            </div>

            <div class="extra-shipment-container row">
                {include file='./admin-order-expanded.tpl'}
            </div>

            <div class="clearfix">&nbsp;</div>
            <div class="col-lg-12 return-link-holder">
                {if isset($orderReturn) && $orderReturn}
                    <a href="{$orderReturnLink|escape:'htmlall':'UTF-8'}" class="btn btn-default">
                        {l s='Initiate return' mod='dpdbaltics'}
                    </a>
                {/if}
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>

{*<script>*}
{*    var dpdShipments = JSON.parse('{$encodedShipments}');*}
{*</script>*}
