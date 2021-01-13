<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /LICENSE
 *
 *  International Registered Trademark & Property of INVERTUS, UAB
 */

namespace Invertus\dpdBaltics\OnBoard\OnBoardSteps;

use DPDBaltics;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\OnBoard\AbstractOnBoardStep;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardButton;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardFastMoveButton;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardParagraph;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardProgressBar;
use Invertus\dpdBaltics\OnBoard\Objects\OnBoardTemplateData;
use Tools;

class StepManualProducts4 extends AbstractOnBoardStep
{
    const FILE_NAME = 'StepManualProducts4';

    public function checkIfRightStep($currentStep) {
        if ($currentStep === (new \ReflectionClass($this))->getShortName()) {
            return true;
        }

        return false;
    }

    public function takeStepData()
    {
        $templateDataObj = new OnBoardTemplateData();

        $templateDataObj->setFastMoveButton(NEW OnBoardFastMoveButton(
            Config::STEP_MANUAL_ZONES_0,
            Config::STEP_FAST_MOVE_BACKWARD
        ));

        if ($this->stepDataService->isAtLeastOneProductActive()) {
            $templateDataObj->setFastMoveButton(NEW OnBoardFastMoveButton(
                Config::STEP_MANUAL_PRICE_RULES_0,
                Config::STEP_FAST_MOVE_FORWARD
            ));
        }

        $templateDataObj->setContainerClass('right-center products');

        $templateDataObj->setParagraph(new OnBoardParagraph(
            $this->module->l('Delivery time is required by PrestaShop, it will be shown to your customer.', self::FILE_NAME)
        ));
        $templateDataObj->setParagraph(new OnBoardParagraph(
            $this->module->l('You can type something like: "Delivery in 2-4 days" or "Trust your package to DPD".', self::FILE_NAME)
        ));

        $templateDataObj->setParagraph(new OnBoardParagraph(
            $this->module->l('You can set delivery time in all languages available in your shop.', self::FILE_NAME)
        ));

        $templateDataObj->setButton(new OnBoardButton(
            $this->module->l('Next', self::FILE_NAME),
            'pull-right btn-light button-border js-dpd-next-step',
            Config::STEP_MANUAL_PRODUCTS_5,
            '.js-contract-delivery-time'
        ));

        $currentProgressBarStep = Config::ON_BOARD_PROGRESS_STEP_3;

        $templateDataObj->setManualConfigProgress(
            $this->module->l(sprintf('Products: %s/%s', $currentProgressBarStep, Config::ON_BOARD_PROGRESS_BAR_PRODUCTS_STEPS), self::FILE_NAME)
        );

        $templateDataObj->setProgressBarObj(new OnBoardProgressBar(
            Config::ON_BOARD_PROGRESS_BAR_SECTIONS,
            $this->stepDataService->getCurrentProgressBarSection(),
            $currentProgressBarStep,
            'step'. $currentProgressBarStep . '-' . Config::ON_BOARD_PROGRESS_BAR_PRODUCTS_STEPS
        ));

        return $templateDataObj->getTemplateData();
    }

    public function takeStepAction()
    {
        if (Tools::isSubmit('ajax')) {
            return;
        }

        $this->stepActionService->ifNotRightControllerReverseStep(
            DPDBaltics::ADMIN_PRODUCTS_CONTROLLER,
            Config::STEP_MANUAL_PRODUCTS_0
        );

        /** If current step is same as set in Configuration at this point it means that page was reloaded */
        $this->stepActionService->ifStepIsSameAsInConfigReverseStep(
            Config::STEP_MANUAL_PRODUCTS_4,
            Config::STEP_MANUAL_PRODUCTS_2
        );
    }
}
