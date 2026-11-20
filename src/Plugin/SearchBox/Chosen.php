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

namespace Invertus\dpdBaltics\Plugin\SearchBox;

use Invertus\dpdBaltics\Plugin\PluginInterface;
use Media;
use Module;
use Smarty;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Chosen implements SearchBoxPluginInterface, PluginInterface
{
    private $placeholderText;

    /**
     * All options that are shown in the search box
     * @var array
     */
    private $availableOptions = [];

    /**
     * Marks "All" as the selected option
     * @var bool
     */
    private $allSelected = false;

    /**
     * JS variables
     * @var array
     */
    private $jsVars = [];

    private $js = [];

    private $css = [];

    /**
     * @var Smarty
     */
    private $smarty;

    /**
     * @var string path to plugin's template
     */
    private $templatePath;

    private $name;

    private $disabled;

    /**
     * E.g. if $col is 3 then the select will have col-xs-3 class
     * @var int $col
     */
    private $col = 4;

    public function __construct(Module $module, Smarty $smarty)
    {
        $this->smarty = $smarty;
        $plugin = 'chosen';
        $pluginPath = Media::getJqueryPluginPath($plugin);
        $this->js[] = $pluginPath['js'];
        $this->js[] = $module->getPathUri().'views/js/admin/search_block.js';
        $this->css[] = key($pluginPath['css']);
    }

    public function setPlaceholderText($text)
    {
        $this->placeholderText = $text;
    }

    public function getJs()
    {
        return $this->js;
    }

    public function getCss()
    {
        return $this->css;
    }

    public function getJsVars()
    {
        $this->initJsVars();

        return $this->jsVars;
    }

    /**
     * Set options that are available to select
     *
     * @param array $availableOptions
     */
    public function setAvailableOptions(array $availableOptions)
    {
        $this->availableOptions = $availableOptions;
    }

    /**
     * Set's the "All selected" option value
     *
     * @param bool $allSelected
     */
    public function setAllSelected($allSelected)
    {
        $this->allSelected = $allSelected;
    }

    /**
     * Sets template path for rendering
     *
     * @param string $templatePath
     */
    public function setTemplatePath($templatePath)
    {
        $this->templatePath = $templatePath;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setJsVar($name, $value)
    {
        $this->jsVars[$name] = $value;
    }

    /**
     * Set the name of search box plugin - used in $_POST
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the name of search box plugin - used in $_POST
     * @param string $name
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * Set col value in bootstrap's measurement
     *
     * @param int $col
     */
    public function setBootstrapCol($col)
    {
        $this->col = $col;
    }

    /**
     * Renders the plugin
     * @return string
     */
    public function render()
    {
        $this->smarty->assign([
            'availableElements' => $this->availableOptions,
            'includeAllOption' => true,
            'allSelected' => $this->allSelected,
            'name' => $this->name,
            'col' => $this->col,
            'disabled' => $this->disabled
        ]);

        return $this->smarty->fetch($this->templatePath);
    }

    protected function initJsVars()
    {
        $this->setJsVar('chosenPlaceholder', $this->placeholderText);
    }
}
