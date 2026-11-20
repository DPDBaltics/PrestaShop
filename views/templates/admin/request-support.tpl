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

<div class="panel">
    <div class="panel-heading">
        <i class="icon-phone-sign"></i>
        {l s='Request support' mod='dpdbaltics'}
    </div>

    <div class="form-wrapper">

        {foreach $requestSupportData as $requestSupportType => $requestSupportItem}
            <div class="form-group clearfix">
                <div id="{$requestSupportType|escape:'html':'UTF-8':false}">
                    <label class="control-label col-lg-3">
                        {$requestSupportItem.name|escape:'html':'UTF-8':false}
                    </label>

                    <div class="col-lg-9">
                    <span class="fixed-width-lg">
                        {$requestSupportItem.value|escape:'html':'UTF-8':false}
                    </span>
                    </div>
                </div>
            </div>
        {/foreach}

        {if $isLogsOn}
            <div class="form-group clearfix">
                <div id="request-support-logs">
                    <label class="control-label col-lg-3">
                        {l s='DPD Logs' mod='dpdbaltics'}
                    </label>

                    <form action="{$downloadLogsAction|escape:'html':'UTF-8':false}" method="post" enctype="multipart/form-data">
                        <div class="col-lg-9">
                            <button
                                    class="btn btn-default"
                                    name="submitDpdDownloadLogs"
                                    type="submit"
                            >
                                <i class="process-icon-download"></i>
                                {l s='Download logs' mod='dpdbaltics'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        {else}
            <div class="clearfix">
                <div class="help-block col-lg-12">
                    <div class="alert alert-info">
                        <span>{l s='Logs are turned off. Please reproduce problem with logs turned on' mod='dpdbaltics'}</span>
                    </div>
                </div>
            </div>
        {/if}

        <div>

        </div>

        <div class="clearfix">
            <div class="help-block col-lg-12">
                <div class="alert alert-info">
                    <span>{l s='Please give this information to your DPD support.' mod='dpdbaltics'}</span>
                </div>
            </div>
        </div>

        <div class="clearfix">
            <div class="help-block col-lg-12">
                <div class="alert alert-info">
                    <p>{l s='Please attach as much as possible data that can help to solve your issue:' mod='dpdbaltics'}</p>
                    <p>{l s='Steps' mod='dpdbaltics'}</p>
                    <p>{l s='Pictures' mod='dpdbaltics'}</p>
                    <p>{l s='Videos' mod='dpdbaltics'}</p>
                    <p>{l s='Logs' mod='dpdbaltics'}</p>
                </div>
            </div>
        </div>
    </div>
</div>