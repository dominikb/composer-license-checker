<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use Dominikb\ComposerLicenseChecker\Contracts\DependencyLoader as DependencyLoaderContract;
use Dominikb\ComposerLicenseChecker\Contracts\DependencyParser;

class DependencyLoader implements DependencyLoaderContract
{
    /**
     * @var DependencyParser
     */
    private $dependencyParser;

    public function __construct(DependencyParser $dependencyParser)
    {
        $this->dependencyParser = $dependencyParser;
    }

    public function loadDependencies(string $composer, string $project): array
    {
        $commandOutput = $this->runComposerLicenseCommand($composer, $project);

        return $this->dependencyParser->parse(join(PHP_EOL, $commandOutput));
    }

    private function runComposerLicenseCommand(string $composer, string $project, string $format = 'json'): array
    {
        $command = sprintf('%s licenses -f %s -d %s', $composer, $format, $project);

        return $this->exec($command);
    }

    protected function exec(string $command)
    {
        exec($command, $output);

        return $output;
    }
}
