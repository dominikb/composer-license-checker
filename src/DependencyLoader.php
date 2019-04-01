<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use Dominikb\ComposerLicenseChecker\Contracts\DependencyLoader as DependencyLoaderContract;

class DependencyLoader implements DependencyLoaderContract
{
    const LINES_BEFORE_DEPENDENCY_VERSIONS = 6;

    public function loadDependencies(string $composer, string $project): array
    {
        $commandOutput = $this->runComposerLicenseCommand($composer, $project);

        $cleanOutput = $this->stripHeadersFromOutput($commandOutput);

        return $this->splitColumnsIntoDependencies($cleanOutput);
    }

    private function runComposerLicenseCommand(string $composer, string $project): array
    {
        $command = sprintf('%s -d %s licenses', $composer, $project);

        return $this->exec($command);
    }

    private function stripHeadersFromOutput(array $output): array
    {
        return array_slice($output, self::LINES_BEFORE_DEPENDENCY_VERSIONS - 1);
    }

    private function splitColumnsIntoDependencies(array $output): array
    {
        $parser = new DependencyParser;

        $mappedToObjects = [];
        foreach ($output as $dependency) {
            $mappedToObjects[] = $parser->parse($dependency);
        }

        return $mappedToObjects;
    }

    protected function exec(string $command)
    {
        exec($command, $output);

        return $output;
    }
}
