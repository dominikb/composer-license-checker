<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use Dominikb\ComposerLicenseChecker\Contracts\DependencyLoaderAware;
use Dominikb\ComposerLicenseChecker\Contracts\LicenseLookupAware;
use Dominikb\ComposerLicenseChecker\Traits\DependencyLoaderAwareTrait;
use Dominikb\ComposerLicenseChecker\Traits\LicenseLookupAwareTrait;
use Symfony\Component\Cache\Simple\NullCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCommand extends Command implements LicenseLookupAware, DependencyLoaderAware
{
    use LicenseLookupAwareTrait, DependencyLoaderAwareTrait;

    const LINES_BEFORE_DEPENDENCY_VERSIONS = 2;

    protected static $defaultName = 'report';

    protected function configure()
    {
        $this->setDefinition(new InputDefinition([
            new InputOption(
                'project-path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Path to directory of composer.json file',
                realpath('.')
            ),
            new InputOption(
                'composer',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to composer executable',
                realpath('./vendor/bin/composer')
            ),
            new InputOption(
                'no-cache',
                null,
                InputOption::VALUE_NONE,
                'Disables caching of license lookups'
            ),
        ]));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dependencies = $this->dependencyLoader->loadDependencies(
            $input->getOption('composer'),
            $input->getOption('project-path')
        );

        $groupedByName = $this->groupDependenciesByLicense($dependencies);

        $shouldCache = ! $input->getOption('no-cache');
        $licenses = $this->lookUpLicenses(array_keys($groupedByName), $output, $shouldCache);

        /* @var License $license */
        $this->outputFormattedLicenses($output, $licenses, $groupedByName);

        return 0;
    }

    /**
     * @param Dependency[] $dependencies
     *
     * @return array
     */
    private function groupDependenciesByLicense(array $dependencies)
    {
        $grouped = [];

        foreach ($dependencies as $dependency) {
            [$license] = $dependency->getLicenses();

            if (! isset($grouped[$license])) {
                $grouped[$license] = [];
            }
            $grouped[$license][] = $dependency;
        }

        return $grouped;
    }

    private function lookUpLicenses(array $licenses, OutputInterface $output, $useCache = true)
    {
        if (! $useCache) {
            $this->licenseLookup->setCache(new NullCache);
        }

        $lookedUp = [];
        foreach ($licenses as $license) {
            $output->writeln("Looking up $license ...");
            $lookedUp[$license] = $this->licenseLookup->lookUp($license);
        }

        return $lookedUp;
    }

    /**
     * @param OutputInterface $output
     * @param License[]       $licenses
     * @param array           $groupedByName
     */
    protected function outputFormattedLicenses(OutputInterface $output, array $licenses, array $groupedByName): void
    {
        foreach ($licenses as $license) {
            $usageCount = count($groupedByName[$license->getShortName()]);
            $headline = sprintf(PHP_EOL.'Count %d - %s (%s)', $usageCount, $license->getShortName(),
                $license->getSource());
            $output->writeln($headline);
            $licenseTable = new Table($output);
            $licenseTable->setHeaders(['CAN', 'CAN NOT', 'MUST']);

            $can = $license->getCan();
            $cannot = $license->getCannot();
            $must = $license->getMust();
            $columnWidth = max(count($can), count($cannot), count($must));

            $can = array_pad($can, $columnWidth, null);
            $cannot = array_pad($cannot, $columnWidth, null);
            $must = array_pad($must, $columnWidth, null);

            $inlineHeading = function ($key) {
                return is_string($key) ? $key : '';
            };

            $can = array_map_keys($can, $inlineHeading);
            $cannot = array_map_keys($cannot, $inlineHeading);
            $must = array_map_keys($must, $inlineHeading);

            for ($i = 0; $i < $columnWidth; $i++) {
                $licenseTable->addRow([
                    'CAN'    => $can[$i],
                    'CANNOT' => $cannot[$i],
                    'MUST'   => $must[$i],
                ]);
            }
            $licenseTable->render();
        }
    }
}
