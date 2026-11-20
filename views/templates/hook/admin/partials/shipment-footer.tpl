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

<!-- Shipment footer -->
<div class="panel-footer card-footer dpd-shipment-footer">
    <button data-loading-text="<i class='icon icon-spinner icon-spin '></i>{l s=' loading...' mod='dpdbaltics'}"
            type="button"
            class="btn btn-default btn-secondary button pull-right float-right js-shipment-save-btn ml-1 js-shipment-action-btn {if $testOrder && !$testMode}disabled{/if}"
            data-action="save_and_print">
        <i class="process-icon-save"></i>
        {if 'download' == $printLabelOption}
            {l s='Save & Download label' mod='dpdbaltics'}
        {else}
            {l s='Save & Print label' mod='dpdbaltics'}
        {/if}
    </button>

    <button data-loading-text="<i class='icon icon-spinner icon-spin '></i>{l s=' loading...' mod='dpdbaltics'}"
            type="button"
            class="btn btn-default btn-secondary button pull-right float-right js-shipment-save-btn js-shipment-action-btn {if $testOrder && !$testMode}disabled{/if}"
            data-action="save">
        <i class="process-icon-save"></i>
        {l s='Save' mod='dpdbaltics'}
    </button>
    <div class="d-flex flex-row float-right">
        <a href="#"
           class="btn btn-default btn-secondary  pull-right js-print-label-btn mr-1"
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
        {if !$isAutomated}
        <a href="{$adminLabelLink|escape:'html':'UTF-8':false}&shipment_id={$shipment->id|escape:'html':'UTF-8':false}"
           class="btn btn-default btn-secondary pull-right"
        >
            <i class="process-icon-save"></i>
            {l s='Download return label' mod='dpdbaltics'}
        </a>
        {/if}
    </div>

    <div class="dpd-print-format-block">
        <div class="pull-right dpd-print-format-input form-group row">
            <div class="col-lg-6 form-row">
                <label class="control-label" >{l s='Printout format' mod='dpdbaltics'}:</label>
                <select name="label_format" class=" col-lg-8 form-control js-printout-format-select"
                        id="{$default_label_format|escape:'html':'UTF-8':false}">
                    {foreach $dpdSelectFormatOptions as $option}
                        <option {if $option.value == $selectedFormatOptionId}selected{/if} value="{$option.value|escape:'html':'UTF-8':false}">
                            {$option.name|escape:'html':'UTF-8':false}
                        </option>
                    {/foreach}
                </select>
            </div>
            <div class="col-lg-6 js-custom-image-select-order custom-image-select-order DPD_DEFAULT_LABEL_POSITION">
                <label class="control-label">{l s='Label position' mod='dpdbaltics'}:</label>
                {include file="../../admin/partials/custom-image-select.tpl"}
            </div>
        </div>
    </div>
</div> <!-- end Shipment -->
