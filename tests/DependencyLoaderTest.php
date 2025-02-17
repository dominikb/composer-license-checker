<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\Contracts\DependencyParser;
use Dominikb\ComposerLicenseChecker\DependencyLoader;
use Dominikb\ComposerLicenseChecker\Exceptions\CommandExecutionException;
use Mockery;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

class DependencyLoaderTest extends TestCase
{
    /**
     * Linux required because of escape characters in the verified command.
     */
    #[Test]
    #[RequiresOperatingSystem('Linux|Darwin')]
    public function it_runs_the_command_with_the_given_inputs()
    {
        $loader = Mockery::mock(DependencyLoader::class, [$this->createNoOpParser()])
                         ->makePartial();

        $command = '';
        $loader->shouldAllowMockingProtectedMethods()
               ->expects('exec')
               ->withArgs(function ($c) use (&$command) {
                   return (bool) ($command = $c);
               })
               ->andReturns([]);

        $loader->loadDependencies('./composerpath/composer-binary', '/some/directory', false);

        $this->assertEquals("'./composerpath/composer-binary' licenses --format json --working-dir '/some/directory'", $command);
    }

    /**
     * Linux required because of escape characters in the verified command.
     */
    #[Test]
    #[RequiresOperatingSystem('Linux|Darwin')]
    public function it_runs_the_command_with_the_given_inputs_without_dev()
    {
        $loader = Mockery::mock(DependencyLoader::class, [$this->createNoOpParser()])
                         ->makePartial();

        $command = '';
        $loader->shouldAllowMockingProtectedMethods()
               ->expects('exec')
               ->withArgs(function ($c) use (&$command) {
                   return (bool) ($command = $c);
               })
               ->andReturns([]);

        $loader->loadDependencies('./composerpath/composer-binary', '/some/directory', true);

        $this->assertEquals("'./composerpath/composer-binary' licenses --no-dev --format json --working-dir '/some/directory'", $command);
    }

    #[Test]
    public function it_throws_on_exec_failure()
    {
        $loader = Mockery::mock(DependencyLoader::class, [$this->createNoOpParser()])
            ->makePartial();

        $loader->shouldAllowMockingProtectedMethods()
            ->expects('exec')
            ->andThrows(CommandExecutionException::class, 'Error when trying to fetch licenses from Composer', Command::INVALID);

        $this->expectException(CommandExecutionException::class);
        $this->expectExceptionCode(2);
        $loader->loadDependencies('./composerpath/composer-binary', '/some/directory', false);
    }

    public function createNoOpParser(): DependencyParser
    {
        return new class implements DependencyParser
        {
            public function parse(string $dependencyOutput): array
            {
                return [];
            }
        };
    }
}
