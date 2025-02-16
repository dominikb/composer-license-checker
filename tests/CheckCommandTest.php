<?php

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\CheckCommand;
use Dominikb\ComposerLicenseChecker\ConstraintViolationDetector;
use Dominikb\ComposerLicenseChecker\Contracts\DependencyLoader;
use Dominikb\ComposerLicenseChecker\Dependency;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CheckCommandTest extends TestCase
{
    /** @var CheckCommand */
    protected $command;
    /** @var Mockery\MockInterface */
    protected $licenseLookup;
    /** @var Mockery\MockInterface */
    protected $dependencyLoader;
    /** @var BufferedOutput */
    protected $output;

    protected function setUp(): void
    {
        $this->command = new CheckCommand;
        $this->licenseLookup = Mockery::mock(\Dominikb\ComposerLicenseChecker\Contracts\LicenseLookup::class);
        $this->dependencyLoader = Mockery::mock(DependencyLoader::class);
        $this->command->setLicenseLookup($this->licenseLookup);
        $this->command->setDependencyLoader($this->dependencyLoader);
        $this->command->setLicenseConstraintHandler(new ConstraintViolationDetector);
        $this->output = new BufferedOutput;
    }

    #[Test]
    public function it_fails_when_a_dependency_has_a_disallowed_license()
    {
        $this->dependencyLoader
            ->allows('loadDependencies')
            ->andReturn([new Dependency('some-dependency', '1.0', ['DISALLOWED'])]);

        $input = new ArrayInput([
            '--composer' => 'path/to/composer',
            '--allowlist' => ['ALLOWED_LICENSE'],
        ], $this->command->getDefinition());

        $result = $this->command->execute($input, $this->output);

        $this->assertSame(Command::FAILURE, $result);
    }

    #[Test]
    public function it_can_allow_licenses_specified_in_a_file()
    {
        $this->dependencyLoader
            ->allows('loadDependencies')
            ->andReturn([
                new Dependency('some-dependency', '4.20', ['MIT']),
                new Dependency('other-dependency', '6.9', ['Apache-2.0']),
            ]);

        $input = new ArrayInput([
            '--composer' => 'path/to/composer',
            '--allowlist' => [join(DIRECTORY_SEPARATOR, [__DIR__, 'allowlist.txt']), 'MIT'],
        ], $this->command->getDefinition());

        $result = $this->command->execute($input, $this->output);
        $this->assertSame(Command::SUCCESS, $result);
    }

    #[Test]
    public function it_can_block_licenses_specified_in_a_file()
    {
        $this->dependencyLoader
            ->allows('loadDependencies')
            ->andReturn([new Dependency('some-dependency', '1.0', ['DISALLOWED_LICENSE'])]);

        $input = new ArrayInput([
            '--composer' => 'path/to/composer',
            '--blocklist' => [join(DIRECTORY_SEPARATOR, [__DIR__, 'blocklist.txt'])],
        ], $this->command->getDefinition());

        $result = $this->command->execute($input, $this->output);
        $this->assertSame(Command::FAILURE, $result);
    }
}
