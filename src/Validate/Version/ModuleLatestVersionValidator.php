<?php
/**
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
 */

namespace Invertus\dpdBaltics\Validate\Version;

use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Infrastructure\Utility\ModuleVersionUtility;
use Invertus\dpdBaltics\Validate\ValidatorInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ModuleLatestVersionValidator implements ValidatorInterface
{
    const FILE_NAME = 'ModuleLatestVersionValidator';

    /** @var ModuleVersionUtility */
    private $moduleVersionUtility;

    public function __construct(ModuleVersionUtility $moduleVersionUtility)
    {
        $this->moduleVersionUtility = $moduleVersionUtility;
    }

    /**
     * Checks and validates if the module is the latest version from GitHub
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function validate(): bool
    {
        try {
            return $this->moduleVersionUtility->isVersionLatest($this->getLatestModuleVersionGithub());
        } catch (\Exception $e) {
            throw new \Exception(sprintf('%s - Unable to get the latest module version from GitHub', self::FILE_NAME));
        }
    }

    private function getLatestModuleVersionGithub(): string
    {
        try {
            $request = curl_init();
            curl_setopt($request, CURLOPT_URL, Config::DPD_GITHUB_REPO_RELEASE_LATEST_URL);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($request, CURLOPT_USERAGENT, 'PrestaShop');
            $response = curl_exec($request);
            curl_close($request);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        unset($request);

        $version = json_decode($response)->tag_name;

        return preg_replace('/^v/', '', $version);
    }
}