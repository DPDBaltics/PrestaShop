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

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', 'test');
}

if (!class_exists('Configuration')) {
    class Configuration
    {
        public static $values = [];

        public static function get($key)
        {
            return isset(self::$values[$key]) ? self::$values[$key] : false;
        }

        public static function updateValue($key, $value)
        {
            self::$values[$key] = $value;

            return true;
        }
    }
}

use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\OnBoard\Builder\OnBoardBlockBuilder;
use Invertus\dpdBaltics\OnBoard\OnBoardStepInterface;
use Invertus\dpdBaltics\OnBoard\Provider\OnBoardStepStrategyProvider;
use Invertus\dpdBaltics\OnBoard\Service\OnBoardService;

class OnBoardServiceTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Configuration::$values = [];
    }

    /**
     * The parcel import JS stores DPD_MANUAL_PARCELS_AFTER_IMPORT_STEP, which no step class handles.
     */
    public function testUnknownStepRendersNothingOnPageLoad()
    {
        Configuration::$values[Config::ON_BOARD_STEP] = 'DPD_MANUAL_PARCELS_AFTER_IMPORT_STEP';

        $this->assertSame('', $this->createService()->makeStepActionWithTemplateReturn());
    }

    public function testUnknownStepReturnsEmptyTemplateToAjax()
    {
        Configuration::$values[Config::ON_BOARD_STEP] = 'DPD_MANUAL_PARCELS_AFTER_IMPORT_STEP';

        $this->assertSame(['onBoardTemplate' => ''], $this->createService()->makeStepActionWithTemplateReturn(true));
    }

    public function testKnownStepStillRenders()
    {
        Configuration::$values[Config::ON_BOARD_STEP] = 'StepKnown';
        $stepData = ['containerClass' => 'center-top', 'progressBar' => false];

        $step = $this->getMockBuilder(OnBoardStepInterface::class)->getMock();
        $step->method('checkIfRightStep')->willReturnCallback(function ($currentStep) {
            return $currentStep === 'StepKnown';
        });
        $step->method('takeStepData')->willReturn($stepData);

        $builder = $this->getMockBuilder(OnBoardBlockBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->once())->method('render')->with($stepData, false)->willReturn('<div>step</div>');

        $service = new OnBoardService(new OnBoardStepStrategyProvider($step), $builder);

        $this->assertSame('<div>step</div>', $service->makeStepActionWithTemplateReturn());
    }

    private function createService()
    {
        $builder = $this->getMockBuilder(OnBoardBlockBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->never())->method('render');

        return new OnBoardService(new OnBoardStepStrategyProvider(), $builder);
    }
}
