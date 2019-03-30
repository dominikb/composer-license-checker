<?php

namespace Dominikb\ComposerLicenseChecker;

use GuzzleHttp\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Dominikb\ComposerLicenseChecker\Contracts\LicenseLookup;

class ComposerLicenseCheckCommand extends Command
{
    const LINES_BEFORE_DEPENDENCY_VERSIONS = 2;

    /** @var OutputInterface */
    protected $output;

    /** @var Contracts\LicenseLookup */
    protected $licenseLookup;

    /** @var InputInterface */
    protected $input;

    public function execute(InputInterface $input, OutputInterface $output, LicenseLookup $licenseLookup = null)
    {
        $this->input = $input;
        $this->output = $output;
        $this->licenseLookup = new \Dominikb\ComposerLicenseChecker\LicenseLookup(new Client());

        $composer = $input->getOption('composer') ?? realpath('./vendor/bin/composer');

        $path = $this->input->getOption('directory') ?? realpath('./');

        $command = sprintf('%s -d %s licenses', $composer, $path);

        exec($command, $output);

        $filteredOutput = $this->filterHeaderOutput($output);

        $dependencies = $this->splitColumnsIntoDependencies($filteredOutput);

        $groupedByLicense = $this->groupDependenciesByLicense($dependencies);

        $licenses = $this->lookUpLicenses(array_keys($groupedByLicense));

        $dependencyTable = new Table($this->output);
        $dependencyTable->setHeaders(['license', 'count']);
        foreach ($groupedByLicense as $license => $items) {
            $dependencyTable->addRow(['license' => $license, 'count' => count($items)]);
        }
        $dependencyTable->render();

        /** @var License $license */
        foreach ($licenses as $license) {
            $headline = sprintf("\nLicense: %s (%s)", $license->getShortName(), $license->getSource());
            $this->output->writeln($headline);
            $licenseTable = new Table($this->output);
            $licenseTable->setHeaders(['CAN', 'CAN NOT', 'MUST']);

            $can = $license->getCan();
            $cannot = $license->getCannot();
            $must = $license->getMust();
            $count = max(count($can), count($cannot), count($must));

            $can = array_pad($can, $count, null);
            $cannot = array_pad($cannot, $count, null);
            $must = array_pad($must, $count, null);

            $inlineHeading = function ($key, $value) {
                if ( ! is_string($key)) {
                    return "";
                }

                return wordwrap($key, 60);
//                return wordwrap("$key\n($value)", 60);
            };

            $can = array_map_keys($can, $inlineHeading);
            $cannot = array_map_keys($cannot, $inlineHeading);
            $must = array_map_keys($must, $inlineHeading);

            for ($i = 0; $i < $count; $i++) {
                $licenseTable->addRow([
                    'CAN'    => $can[$i],
                    'CANNOT' => $cannot[$i],
                    'MUST'   => $must[$i],
                ]);
            }
            $licenseTable->render();
        }
    }

    private function filterHeaderOutput(array $output): array
    {
        for ($i = 0; $i < count($output); $i++) {
            if ($output[$i] === "") {
                return array_slice($output, $i + self::LINES_BEFORE_DEPENDENCY_VERSIONS);
            }
        }

        throw ParsingException::reason("Could not filter out headers");
    }

    private function splitColumnsIntoDependencies(array $output): array
    {
        $mappedToObjects = [];
        foreach ($output as $dependency) {
            $normalized = preg_replace("/\\s+/", " ", $dependency);
            $columns = explode(" ", $normalized);
            $mappedToObjects[] = (new Dependency)
                ->setName($columns[0])
                ->setVersion($columns[1])
                ->setLicense($columns[2]);
        }

        return $mappedToObjects;
    }

    private function groupDependenciesByLicense(array $dependencies)
    {
        $grouped = [];

        foreach ($dependencies as $dependency) {
            if ( ! isset($grouped[$license = $dependency->getLicense()])) {
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
