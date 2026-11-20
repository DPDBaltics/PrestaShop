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
<div class="js-shipment-block dpd-shipment-panel panel card">
    <!-- Shipment heading -->
    <div class="panel-heading card-header">
        <span class="card-header-title">
            {l s='Shipment' mod='dpdbaltics'}
        </span>
        <div class="tree-actions pull-right float-right">
            <button class="btn btn-sm btn-danger js-button-remove hidden d-none " type="button">
                <span>
                    <i class="icon icon-remove"></i>
                    {l s='Remove' mod='dpdbaltics'}
                </span>
            </button>
            <button class="btn btn-sm btn-default btn-secondary js-toggle-shipment" type="button">
                <span class="js-shipment-close">
                    <i class="icon-collapse-alt"></i>
                    {l s='Close' mod='dpdbaltics'}
                </span>

                <span class="js-shipment-expand d-none hidden">
                    <i class="icon-plus-square-o" aria-hidden="true"></i>
                    {l s='Expand' mod='dpdbaltics'}
                </span>
            </button>
        </div>
    </div> <!-- end Shipment heading -->

    <!-- Shipment body -->
    <div class="panel-body card-body">
        <div class="alert alert-danger js-shipment-errors d-none hidden" role="alert">
            <a href="#" class="close js-error-close">&times;</a>
            <span class="js-error-msg"></span>
        </div>
        <div class="js-shipment-error message-text-box alert alert-danger d-none hidden"></div>
        <div class="alert alert-info js-shipment-disabled-info d-none hidden">
            {l s='Shipment cannot be changed because label is already printed.' mod='dpdbaltics'}
        </div>

        <div class="alert alert-warning js-shipment-disabled-warning d-none hidden">
            {l s='Unable to find any available service.' mod='dpdbaltics'}<a
                    href="{$contractPageLink|escape:'htmlall':'UTF-8'}">{l s='Click here' mod='dpdbaltics'}</a>
            {l s=' to add new service.' mod='dpdbaltics'}
        </div>

        <div class="alert alert-warning js-contract-change-warning d-none hidden">
            {l s='Your DPD user has changed. Please check if your contract, service and sender address is correct.' mod='dpdbaltics'}
        </div>

        <!-- General data -->
        <div class="js-general-data">
            <div class="row form-group">
                <div class="col-lg-4">
                    <div class="form-row row type-choice">
                        <label class="control-label col-lg-12">
                            {l s='Product' mod='dpdbaltics'}
                        </label>
                        <div class="col-lg-12">
                            <select class=" form-control js-contract-select" title="{l s='Service' mod='dpdbaltics'}"
                                    name="product">
                                {foreach $dpdProducts as $product}
                                    <option
                                            value="{$product.id_dpd_product|escape:'html':'UTF-8':false}"
                                            {if $product.id_dpd_product === $shipment->id_service}selected{/if}
                                    >
                                        {$product.name|escape:'html':'UTF-8':false}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                {if $isAbove177}
                    {include file='./date-picker-above-177.tpl'}
                {else}
                    {include file='./date-picker-below-177.tpl'}
                {/if}
            </div>

            <div class="dpd-separator"></div>
            <div class="row form-group">
                <div class="col-lg-2">
                    <div class="form-row">
                        <label class="control-label  col-lg-12">
										<span class="label-tooltip" data-toggle="popover" data-html="true" title=""
                                              data-original-title="This reference will be displayed on label">
                                            {l s='Reference 1' mod='dpdbaltics'}
										</span>

                        </label>
                        <div class="col-lg-12">
                            <input type="text"
                                   name="reference1"
                                   title="{l s='Reference 1' mod='dpdbaltics'}"
                                   class="form-control js-shipment-reference-input"
                                   value="{$shipment->reference1|escape:'html':'UTF-8':false}"
                            >
                        </div>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-row">
                        <label class="control-label col-lg-12">
                            {l s='Reference 2' mod='dpdbaltics'}
                        </label>
                        <div class="col-lg-12">
                            <input type="text"
                                   name="reference2"
                                   title="{l s='Reference 2' mod='dpdbaltics'}"
                                   class="form-control js-shipment-reference-input"
                                   value="{$shipment->reference2|escape:'html':'UTF-8':false}"
                            >
                        </div>
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="form-row">
                        <label class="control-label col-lg-12">
                            {l s='Reference 3' mod='dpdbaltics'}
                        </label>
                        <div class="col-lg-12">
                            <input type="text"
                                   name="reference3"
                                   title="{l s='Reference 3' mod='dpdbaltics'}"
                                   class=" form-control js-shipment-reference-input"
                                   value="{$shipment->reference3|escape:'html':'UTF-8':false}"
                            >
                        </div>
                    </div>
                </div>

                <div class="col-lg-2">
                    <div class="form-row">
                        <label class="control-label col-lg-12 d-none hidden">
                            {l s='Reference 4' mod='dpdbaltics'}
                        </label>
                        <div class="col-lg-12">
                            <input type="hidden"
                                   name="reference4"
                                   title="{l s='Reference 4' mod='dpdbaltics'}"
                                   class=" form-control js-shipment-reference-input"
                                   value="{$shipment->reference4|escape:'html':'UTF-8':false}"
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="dpd-separator"></div>

            <div class="row form-group">
                <div class="col-lg-4">
                    <div class="form-row">
                        <label class="control-label col-lg-12">
                            {l s='Weight (kg)' mod='dpdbaltics'}
                        </label>
                        <div class="col-lg-12">
                            <input type="text"
                                   name="weight"
                                   title="{l s='Weight (kg)' mod='dpdbaltics'}"
                                   class=" form-control js-shipment-weight-input"
                                   value="{$shipment->weight|escape:'html':'UTF-8':false}"
                            >
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-row">
                        <label class="control-label col-lg-12">
                            {l s='Parcels amount' mod='dpdbaltics'}
                        </label>
                        <div class="col-lg-12">
                            <input type="text"
                                   name="parcel_amount"
                                   title="{l s='Parcels amount' mod='dpdbaltics'}"
                                   class="form-control js-shipment-parcels-input"
                                   value="{$shipment->num_of_parcels|escape:'html':'UTF-8':false}"
                            >
                        </div>
                    </div>
                </div>
                <div class="col-lg-4" {if !$isCodPayment}style="display: none" {/if}>
                    <div class="form-row">
                        <label class="control-label col-lg-12">
                            {l s='Goods price' mod='dpdbaltics'}
                        </label>
                        <div class="col-lg-12">
                            <input type="text"
                                   name="goods_price"
                                   title="{l s='Goods price' mod='dpdbaltics'}"
                                   class="form-control js-shipment-price-input"
                                   value="{$shipment->goods_price|escape:'html':'UTF-8':false}"
                            >
                        </div>
                    </div>
                </div>
            </div>
            {if isset($orderDeliveryTime)}

                <div class="row form-group">
                    <div class="col-lg-4">
                        <div class="form-row row type-choice">
                            <label class="control-label col-lg-12">
                                {l s='Delivery time' mod='dpdbaltics'}
                            </label>
                            <div class="col-lg-12">
                                <select class="form-control"
                                        title="{l s='Delivery time' mod='dpdbaltics'}"
                                        name="delivery_time">
                                    {html_options options=$deliveryTimes selected=$orderDeliveryTime}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}

        </div>

        <div class="dpd-separator"></div>

        <div id="pudoTemplate" {if !$is_pudo}class="hidden d-none"{/if}>
            {include file='./pudo-address.tpl'}
        </div>

    </div>
</div> <!-- end General data -->
