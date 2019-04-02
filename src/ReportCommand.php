<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Dominikb\ComposerLicenseChecker\Contracts\LicenseLookupAware;
use Dominikb\ComposerLicenseChecker\Traits\LicenseLookupAwareTrait;
use Dominikb\ComposerLicenseChecker\Contracts\DependencyLoaderAware;
use Dominikb\ComposerLicenseChecker\Traits\DependencyLoaderAwareTrait;

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
        ]));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dependencies = $this->dependencyLoader->loadDependencies(
            $input->getOption('composer'),
            $input->getOption('project-path')
        );

        $groupedByLicense = $this->groupDependenciesByLicense($dependencies);

        $licenses = $this->lookUpLicenses(array_keys($groupedByLicense));

        /** @var License $license */
        foreach ($licenses as $license) {
            $usageCount = count($groupedByLicense[$license->getShortName()]);
            $headline = sprintf("\nCount %d - %s (%s)", $usageCount, $license->getShortName(), $license->getSource());
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

    private function lookUpLicenses(array $licenses)
    {
        $lookedUp = [];
        foreach ($licenses as $license) {
            $lookedUp[$license] = $this->licenseLookup->lookUp($license);
        }

        return $lookedUp;
    }
}
