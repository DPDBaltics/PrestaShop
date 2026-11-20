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

<div id="content" class="dpd-on-board-bottom-progress-container bootstrap with-tabs">
    <div class="dpd-on-board-bottom-progress">
        {for $section = 1 to $onBoardTemplateData.progressBar->sections}
            <div class="dpd-on-board-progress-third">
                <div class="dpd-on-board-checkpoint">
                    {if $section < $onBoardTemplateData.progressBar->currentSection || ($section === $onBoardTemplateData.progressBar->currentSection && $onBoardTemplateData.progressBar->currentStep > 1)}
                        <img class="dpd-on-board-checkpoint-logo" src="{$dpdPathUri|escape:'html':'UTF-8':false}views/img/on-board/logo.png" alt="{l s='DPD logo' mod='dpdbaltics'}">
                    {else}
                        {l s='%s' sprintf=[$section] mod='dpdbaltics'}
                    {/if}
                </div>

                {if $onBoardTemplateData.progressBar->currentSection === $section}
                    <div class="dpd-on-board-truck {$onBoardTemplateData.progressBar->truckProgressClass|escape:'html':'UTF-8':false}">
                        <img src="{$dpdPathUri|escape:'html':'UTF-8':false}views/img/on-board/truck.png" alt="{l s='On-board truck' mod='dpdbaltics'}">
                    </div>
                {/if}

                {if $section == 3}
                    <div class="dpd-on-board-checkpoint last-checkpoint">
                        <img class="dpd-on-board-reward" src="{$dpdPathUri|escape:'html':'UTF-8':false}views/img/on-board/reward.png" alt="{l s='On-board reward' mod='dpdbaltics'}">
                    </div>
                {/if}
            </div>
        {/for}
    </div>
</div>