<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\Contracts\DependencyParser;
use Dominikb\ComposerLicenseChecker\DependencyLoader;
use Mockery;

class DependencyLoaderTest extends TestCase
{
    /** @test */
    public function it_runs_the_command_with_the_given_inputs()
    {
        $loader = Mockery::mock(DependencyLoader::class, [$this->createNoOpParser()])
                         ->makePartial();

        $command = '';
        $loader->shouldAllowMockingProtectedMethods()
               ->shouldReceive('exec')
               ->once()
               ->withArgs(function ($c) use (&$command) {
                   return (bool) ($command = $c);
               })
               ->andReturn([]);

        $loader->loadDependencies('./composerpath/composer-binary', '/some/directory');

        $this->assertEquals('./composerpath/composer-binary licenses -f json -d /some/directory', $command);
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
