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

namespace Invertus\dpdBaltics\ConsoleCommand;

use Country;
use Invertus\dpdBaltics\Provider\ZoneRangeProvider;
use Invertus\dpdBaltics\Service\Import\API\ParcelShopImport;
use Module;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for importing parcel shops.
 *
 * Usage:
 *   php bin/console dpdbaltics:update-parcel-shops --country=PL
 *   php bin/console dpdbaltics:update-parcel-shops --all
 *
 * Cron setup (daily at 2 AM):
 *   0 2 * * * cd /var/www/html && php bin/console dpdbaltics:update-parcel-shops --all
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class UpdateParcelShopsCommand extends Command
{
    protected static $defaultName = 'dpdbaltics:update-parcel-shops';

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var ParcelShopImport|null
     */
    private $parcelShopImport;

    /**
     * @var ZoneRangeProvider|null
     */
    private $zoneRangeProvider;

    /**
     * @var \DPDBaltics|null
     */
    private $module;

    protected function configure()
    {
        $this
            ->setDescription('Import/update DPD parcel shops from API')
            ->addOption(
                'country',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Country ISO code to import (e.g., PL, LT, LV, EE)'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Import all default countries (LT, LV, EE, PL)'
            )
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command imports parcel shops from DPD API.

Import a single country:
  <info>php %command.full_name% --country=PL</info>

Import all countries from configured zone ranges:
  <info>php %command.full_name% --all</info>

Cron setup (daily at 2 AM):
  <comment>0 2 * * * cd /var/www/html && php bin/console dpdbaltics:update-parcel-shops --all</comment>

This command is recommended for importing parcel shops as it has no timeout limitations
unlike the web interface which may timeout for countries with many parcel shops.
EOF
            );
    }

    /**
     * Initialize services from the module's container.
     */
    private function initServices()
    {
        if ($this->module !== null) {
            return;
        }

        $this->module = Module::getInstanceByName('dpdbaltics');

        if (!$this->module) {
            throw new \RuntimeException('DPD Baltics module is not installed or not active.');
        }

        $this->parcelShopImport = $this->module->getService('invertus.dpdbaltics.service.import.api.parcel_shop_import');
        $this->zoneRangeProvider = $this->module->getService('invertus.dpdbaltics.provider.zone_range_provider');
        $this->logger = $this->module->getService('invertus.dpdbaltics.logger.logger');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize services from module's container
        try {
            $this->initServices();
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return 1;
        }

        $country = $input->getOption('country');
        $all = $input->getOption('all');

        if (!$country && !$all) {
            $output->writeln('<error>Please specify --country=XX or --all</error>');
            $output->writeln('');
            $output->writeln('Examples:');
            $output->writeln('  php bin/console dpdbaltics:update-parcel-shops --country=PL');
            $output->writeln('  php bin/console dpdbaltics:update-parcel-shops --all');
            return 1;
        }

        // Get countries to import
        if ($all) {
            $countries = $this->getCountriesToImport();
            if (empty($countries)) {
                $output->writeln('<error>No countries configured in zone ranges.</error>');
                $output->writeln('Please configure zone ranges in DPD module settings first.');
                return 1;
            }
        } else {
            $countries = [strtoupper($country)];
        }
        $totalStartTime = microtime(true);
        $hasError = false;

        $output->writeln('');
        $output->writeln('<info>DPD Parcel Shop Import</info>');
        $output->writeln(str_repeat('=', 50));
        $output->writeln('');

        foreach ($countries as $countryCode) {
            $output->write(sprintf('Importing <comment>%s</comment>... ', $countryCode));

            $startTime = microtime(true);

            try {
                $result = $this->parcelShopImport->importParcelShops($countryCode);

                $elapsed = round(microtime(true) - $startTime, 1);

                if (isset($result['success']) && $result['success']) {
                    $output->writeln(sprintf('<info>OK</info> (%ss)', $elapsed));
                    if (isset($result['success_message'])) {
                        $output->writeln(sprintf('  %s', $result['success_message']));
                    }
                } else {
                    $hasError = true;
                    $output->writeln(sprintf('<error>FAILED</error> (%ss)', $elapsed));
                    if (isset($result['error'])) {
                        $output->writeln(sprintf('  <error>%s</error>', $result['error']));
                    }
                }
            } catch (\Exception $e) {
                $hasError = true;
                $elapsed = round(microtime(true) - $startTime, 1);
                $output->writeln(sprintf('<error>ERROR</error> (%ss)', $elapsed));
                $output->writeln(sprintf('  <error>%s</error>', $e->getMessage()));

                if ($this->logger) {
                    $this->logger->error(sprintf(
                        '[CLI] Import failed for %s: %s',
                        $countryCode,
                        $e->getMessage()
                    ));
                }
            }

            $output->writeln('');
        }

        $totalElapsed = round(microtime(true) - $totalStartTime, 1);
        $output->writeln(str_repeat('=', 50));
        $output->writeln(sprintf('Total time: <comment>%ss</comment>', $totalElapsed));
        $output->writeln('');

        return $hasError ? 1 : 0;
    }

    /**
     * Get countries to import from zone range configuration.
     * Falls back to active countries if no zone ranges configured.
     *
     * @return array
     */
    private function getCountriesToImport()
    {
        // First try to get countries from zone ranges (same as CronJob.php)
        $countries = $this->zoneRangeProvider->getAllZoneRangesCountryIsoCodes();

        if (!empty($countries)) {
            return $countries;
        }

        // Fallback: get all active countries
        $activeCountries = Country::getCountries((int) \Configuration::get('PS_LANG_DEFAULT'), true);
        $countryCodes = [];

        foreach ($activeCountries as $country) {
            if (!empty($country['iso_code'])) {
                $countryCodes[] = strtoupper($country['iso_code']);
            }
        }

        return $countryCodes;
    }
}
