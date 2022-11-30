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

<div class="dpd-on-board-images-container">
    <img class="dpd-on-board-mascot" src="{$dpdPathUri}views/img/on-board/mascot.png" alt="{l s='On-board mascot' mod='dpdbaltics'}">
</div>

<div class="dpd-on-board-icons-container">
    <div class="dpd-on-close-container">
        <div class="dpd-on-board-close dpd-on-board-top-button js-dpd-stop-on-board">
            <div class="leftright"></div>
            <div class="rightleft"></div>
        </div>
    </div>
    <div class="dpd-on-pause-container">
        <div class="dpd-on-board-pause dpd-on-board-top-button js-dpd-pause-on-board">
            <i class="icon-pause"></i>
        </div>
    </div>

    {if !empty($onBoardTemplateData.fastMoveButtons)}
        {foreach $onBoardTemplateData.fastMoveButtons as $fastMoveButton}
            <div class="dpd-on-fast-move-container  {$fastMoveButton->direction}">
                <div
                    class="dpd-on-fast-move dpd-on-board-top-button js-dpd-next-step"
                    data-next-step="{$fastMoveButton->step}"
                >
                    <i class="icon-{$fastMoveButton->direction}"></i>
                </div>
            </div>
        {/foreach}
    {/if}
</div>

<div class="dpd-on-board-text-container">
    <p class="dpd-on-board-heading">{if $onBoardTemplateData.heading}{$onBoardTemplateData.heading}{/if}</p>

    {if !empty($onBoardTemplateData.paragraphs)}
        {foreach $onBoardTemplateData.paragraphs as $paragraph}
            <p class="dpd-on-board-text {if $paragraph->class}{$paragraph->class}{/if}">{$paragraph->text}</p>
        {/foreach}
    {/if}
</div>

{if $onBoardTemplateData.importedMainFilesDataArray}
    <div class="dpd-on-board-main-import-container">
        {foreach $onBoardTemplateData.importedMainFilesDataArray as $importedMainFile}
            <div class="dpd-on-board-main-import-item">
                <i class="{$importedMainFile.iconClass}"></i><span>{$importedMainFile.name}</span>
            </div>
        {/foreach}
    </div>
{/if}

<div class="dpd-on-board-buttons-container clearfix">
    {if !empty($onBoardTemplateData.buttons)}
        {foreach $onBoardTemplateData.buttons as $button}
            <button
                    class="dpd-on-board-button btn {if $button->class}{$button->class}{/if}"
                    {if $button->nextStep}data-next-step="{$button->nextStep}"{/if}
                    {if $button->validateField}data-validate="{$button->validateField}"{/if}
            >
                {$button->text}
            </button>
        {/foreach}
    {/if}
</div>

{if $onBoardTemplateData.manualConfigProgress}
    <p class="dpd-on-board-manual-config-progress">{$onBoardTemplateData.manualConfigProgress}</p>
{/if}