<?php

namespace Dominikb\ComposerLicenseChecker;

use Dominikb\ComposerLicenseChecker\Contracts\LicenseConstraintAware;
use Dominikb\ComposerLicenseChecker\Contracts\LicenseLookupAware;
use Dominikb\ComposerLicenseChecker\Exceptions\CommandExecutionException;
use Dominikb\ComposerLicenseChecker\Exceptions\ParsingException;
use Dominikb\ComposerLicenseChecker\Traits\LicenseConstraintAwareTrait;
use Dominikb\ComposerLicenseChecker\Traits\LicenseLookupAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command implements LicenseLookupAware, LicenseConstraintAware
{
    use LicenseLookupAwareTrait, LicenseConstraintAwareTrait;

    const LINES_BEFORE_DEPENDENCY_VERSIONS = 2;

    /** @var ConsoleLogger */
    private $logger;

    /**
     * @throws ParsingException
     * @throws CommandExecutionException
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->logger = new ConsoleLogger($output);

        $this->ensureCommandCanBeExecuted();

        $dependencies = $this->loadDependencies($input);

        $violations = $this->determineViolations($dependencies, $input->getOption('blacklist'), $input->getOption('whitelist'));

        $this->handleViolations($violations);
    }

    /**
     * @throws CommandExecutionException
     */
    private function ensureCommandCanBeExecuted(): void
    {
        if (! $this->licenseLookup) {
            throw new CommandExecutionException('LicenseLookup must be set via setLicenseLookup() before the command can be executed!');
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
            $columns = explode(" ", $normalized);
            $mappedToObjects[] = (new Dependency)
                ->setName($columns[0])
                ->setVersion($columns[1])
                ->setLicense($columns[2])
            ;
        }

        return $mappedToObjects;
    }

    private function determineViolations(array $dependencies, array $blacklist, array $whitelist): array
    {
        $this->licenseConstraintHandler->setBlacklist($blacklist);
        $this->licenseConstraintHandler->setWhitelist($whitelist);

        return $this->licenseConstraintHandler->detectViolations($dependencies);
    }

    /**
     * @param ConstraintViolation[] $violations
     */
    private function handleViolations(array $violations): void
    {
        foreach($violations as $violation) {
            if ($violation->hasViolators()) {
                $this->logger->error($violation->getTitle());
                $this->reportViolators($violation->getViolators());
            }
        }

        if ($this->logger->hasErrored()) {
            die(1);
        }

        die(0);
    }

    /**
     * @param Dependency[] $violators
     */
    private function reportViolators(array $violators): void
    {
        $byLicense = [];
        foreach($violators as $violator) {
            if (! isset($byLicense[$violator->getLicense()])) {
                $byLicense[$violator->getLicense()] = [];
            }
            $byLicense[$violator->getLicense()][] = $violator;
        }

        foreach($byLicense as $license => $violators) {
            $violatorNames = array_map(function(Dependency $dependency) {
                return sprintf('"%s"', $dependency->getName());
            }, $violators);

            $this->logger->notice("$license:");
            $this->logger->info(implode(',', $violatorNames));
        }
    }
}
