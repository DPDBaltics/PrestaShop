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

namespace Invertus\dpdBaltics\OnBoard\Service;

use Configuration;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\OnBoard\Builder\OnBoardBlockBuilder;
use Invertus\dpdBaltics\OnBoard\Provider\OnBoardStepStrategyProvider;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OnBoardService
{
    /**
     * @var OnBoardStepStrategyProvider
     */
    private $onBoardStepProvider;

    /**
     * @var OnBoardBlockBuilder
     */
    private $onBoardTemplateBuilder;

    public function __construct(
        OnBoardStepStrategyProvider $onBoardStepProvider,
        OnBoardBlockBuilder $onBoardTemplateBuilder
    ) {
        $this->onBoardStepProvider = $onBoardStepProvider;
        $this->onBoardTemplateBuilder = $onBoardTemplateBuilder;
    }

    public function makeStepActionWithTemplateReturn($ajaxNextStep = false)
    {
        $currentOnBoardStep = Configuration::get(Config::ON_BOARD_STEP);

        $this->onBoardStepProvider->setOnBoardStrategyByStep($currentOnBoardStep);
        $this->onBoardStepProvider->takeStepAction();

        return $this->returnStepTemplate($ajaxNextStep);
    }

    public function returnStepTemplate($ajaxNextStep)
    {
        $currentOnBoardStep = Configuration::get(Config::ON_BOARD_STEP);

        $this->onBoardStepProvider->setOnBoardStrategyByStep($currentOnBoardStep);

        $onBoardTemplateData = $this->onBoardStepProvider->takeStepData();

        if (!$onBoardTemplateData) {
            return $ajaxNextStep ? ['onBoardTemplate' => ''] : '';
        }

        return $this->onBoardTemplateBuilder->render($onBoardTemplateData, $ajaxNextStep);
    }
}
