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

use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\OnBoard\Builder\OnBoardBlockBuilder;
use Invertus\dpdBaltics\OnBoard\Provider\OnBoardStepStrategyProvider;
use Invertus\dpdBaltics\OnBoard\Service\OnBoardService;
use Invertus\dpdBaltics\OnBoard\Service\OnBoardStepActionService;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDAjaxOnBoardController extends AbstractAdminController
{
    /**
     * Process AJAX call
     */
    public function postProcess()
    {
        if (!Tools::getIsset('ajax')) {
            return;
        }

        $action = Tools::getValue('action');

        if ($action === 'nextOnBoardStep') {
            $nextStep = Tools::getValue('nextStep');

            $this->nextOnBoardStep($nextStep);
        }

        if ($action === 'stopOnBoard') {
            $this->stopOnBoard();
        }

        $response = array(
            'status' => false,
            'message' => $this->module->l('Unexpected error occurred.'),
        );

        return $this->returnResponse($response);
    }

    private function nextOnBoardStep($nextStep)
    {
        if (!$nextStep) {
            return;
        }

        if (!Configuration::updateValue(Config::ON_BOARD_STEP, $nextStep) ) {
            $this->returnResponse(
                array(
                    'status' => false,
                )
            );
        }

        /** @var OnBoardService $onBoardService */
        $onBoardService = $this->module->getModuleContainer('invertus.dpdbaltics.on_board.service.on_board_service');
        $onBoardTemplateData = $onBoardService->makeStepActionWithTemplateReturn(true);
        $currentOnBoardStep = Configuration::get(Config::ON_BOARD_STEP);

        $this->returnResponse(
            array(
                'status' => true,
                'currentStep' => $currentOnBoardStep,
                'stepTemplate' => $onBoardTemplateData,
            )
        );
    }

    private function stopOnBoard()
    {
        /** @var OnBoardStepActionService $stepActionService */
        $stepActionService = $this->module->getModuleContainer('invertus.dpdbaltics.on_board.service.on_board_step_action_service');
        $disableOnBoardResponse = $stepActionService->disableOnBoarding();

        if (Tools::getIsset('pauseOnBoard') && Tools::getValue('pauseOnBoard')) {
            $disableOnBoardResponse = $stepActionService->pauseOnBoarding();
        }

        $this->returnResponse(array(
            'status' => $disableOnBoardResponse,
        ));
    }

    /**
     * Return ajax response
     *
     * @param array $response
     * @throws PrestaShopException
     */
    private function returnResponse(array $response)
    {
        $response = json_encode($response);
        $this->ajaxRender($response);
        exit;
    }
}