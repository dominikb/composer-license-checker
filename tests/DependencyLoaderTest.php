<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\DependencyLoader;
use Mockery;

class DependencyLoaderTest extends TestCase
{
    /** @test */
    public function it_runs_the_command_with_the_given_inputs()
    {
        $loader = Mockery::mock(DependencyLoader::class)
                         ->shouldAllowMockingProtectedMethods()
                         ->makePartial();

        $command = '';
        $loader->shouldReceive('exec')
               ->once()
               ->withArgs(function ($c) use (&$command) {
                   return (bool) ($command = $c);
               })
               ->andReturn([
                   'Name: dominikb/composer-license-checker',
                   'Version: dev-master',
                   'Licenses: MIT',
                   'Dependencies:',
                   '',
                   'Name                                  Version              License   ',
                   'dominikb/composer-license-checker     dev-master f12312    MIT',
               ]);

        $loader->loadDependencies('./composerpath/composer-binary', '/some/directory');

        $this->assertEquals('./composerpath/composer-binary -d /some/directory licenses', $command);
    }
}
