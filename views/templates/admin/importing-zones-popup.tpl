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

<div id="import-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" data-backdrop="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="text-left">
                <img style="padding: 10px" src="{$loading_gif|escape:'html':'UTF-8':false}"/>
                <label id="js-loading" for="message-text" class="col-form-label">{l s='Loading...' mod='dpdbaltics'}</label>
                <label id="js-importing-zones" for="message-text" class="col-form-label" style="display: none">{l s='Importing zones...' mod='dpdbaltics'}</label>
            </div>
        </div>
    </div>
</div>
{if $selectedCountry === 'LV'}
    <div id="import-modal-confirm-latvia" class="modal fade import-modal-confirm" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" data-backdrop="false" data-country="latvia" >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close js-close-zone-import js-import-zones-close-button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">{l s='Do you want to import Latvia zones?' mod='dpdbaltics'}</h4>
                </div>
                <div class="modal-footer">
                    <button id="import-zones-close-button" type="button" class="btn btn-default js-import-zones-close-button" data-dismiss="modal">
                        {l s='Close' mod='dpdbaltics'}
                    </button>

                    <button data-country="latvia" type="button" class="btn btn-primary import-zones-button">{l s='Import' mod='dpdbaltics'}</button>
                </div>
            </div>
        </div>
    </div>
{/if}
{if $selectedCountry === 'LT'}
    <div id="import-modal-confirm-lithuania" class="modal fade import-modal-confirm" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" data-backdrop="false" data-country="lithuania" >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close js-close-zone-import js-import-zones-close-button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">{l s='Do you want to import Lithuania zones?' mod='dpdbaltics'}</h4>
                </div>
                <div class="modal-footer">
                    <button id="import-zones-close-button" type="button" class="btn btn-default js-import-zones-close-button" data-dismiss="modal">
                        {l s='Close' mod='dpdbaltics'}
                    </button>

                    <button data-country="lithuania" type="button" class="btn btn-primary import-zones-button">{l s='Import' mod='dpdbaltics'}</button>
                </div>
            </div>
        </div>
    </div>
{/if}