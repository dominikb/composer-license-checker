<?php declare(strict_types = 1);


namespace Dominikb\ComposerLicenseChecker;


use Dominikb\ComposerLicenseChecker\Contracts\LicenseLookupAware;
use Dominikb\ComposerLicenseChecker\Exceptions\ParsingException;
use Dominikb\ComposerLicenseChecker\Traits\LicenseLookupAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCommand extends Command implements LicenseLookupAware
{
    use LicenseLookupAwareTrait;

    const LINES_BEFORE_DEPENDENCY_VERSIONS = 2;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dependencies = $this->loadDependencies($input);

        $groupedByLicense = $this->groupDependenciesByLicense($dependencies);

        $licenses = $this->lookUpLicenses(array_keys($groupedByLicense));


        /** @var License $license */
        foreach ($licenses as $license) {
            $usageCount= count($groupedByLicense[$license->getShortName()]);
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
                return is_string($key) ? $key : "";
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
     * @param InputInterface $input
     *
     * @return array
     * @throws ParsingException
     */
    private function loadDependencies(InputInterface $input): array
    {
        $commandOutput = $this->runComposerLicenseCommand($input);

        $cleanOutput = $this->stripHeadersFromOutput($commandOutput);

        return $this->splitColumnsIntoDependencies($cleanOutput);
    }

    private function runComposerLicenseCommand(InputInterface $input): array
    {
        $composer = $input->getOption('composer') ?? realpath('./vendor/bin/composer');
        $projectPath = $input->getOption('project-path') ?? realpath('./');

        $command = sprintf('%s -d %s licenses', $composer, $projectPath);

        exec($command, $output);

        return $output;
    }

    /**
     * @throws ParsingException
     */
    private function stripHeadersFromOutput(array $output): array
    {
        for ($i = 0; $i < count($output); $i++) {
            if ($output[$i] === "") {
                return array_slice($output, $i + self::LINES_BEFORE_DEPENDENCY_VERSIONS);
            }
        }

        throw new ParsingException('Could not filter out headers!');
    }

    private function splitColumnsIntoDependencies(array $output): array
    {
        $mappedToObjects = [];
        foreach ($output as $dependency) {
            $normalized = preg_replace("/\\s+/", " ", $dependency);
            $normalized = preg_replace("(\(|\))", "", $normalized);
            $normalized = preg_replace("/ or /", ", ", $normalized);
            $normalized = preg_replace("/, /", " ", $normalized);
            $columns = explode(" ", $normalized);
            $mappedToObjects[] = (new Dependency)
                ->setName($columns[0])
                ->setVersion($columns[1])
                ->setLicense($columns[2])
            ;
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
