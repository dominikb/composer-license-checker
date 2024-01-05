<?php

declare(strict_types=1);


use Dominikb\ComposerLicenseChecker\Dependency;
use Dominikb\ComposerLicenseChecker\Tests\TestCase;
use Dominikb\ComposerLicenseChecker\JSONDependencyParser;

class JSONDependencyParserTest extends TestCase
{
    /** @var \Dominikb\ComposerLicenseChecker\Contracts\DependencyParser */
    private $parser;

    private $STUB = <<<JSON
        {
            "name": "dominikb/composer-license-checker",
            "version": "2.5.0",
            "license": [
                "MIT"
            ],
            "dependencies": <<<DEPENDENCIES>>>
        }
        JSON;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->parser = new JSONDependencyParser();
    }

    /** @test */
    public function it_parses_a_simple_dependency()
    {
        $output = $this->dependenciesOutput([
            'dominikb/composer-license-checker' => [
                'version' => '2.5.0',
                'license' => ['MIT']
            ]
        ]);

        $this->assertDependencyMatches(
            $this->parser->parse($output)[0],
            'dominikb/composer-license-checker',
            '2.5.0',
            'MIT'
        );
    }

    /** @test */
    public function it_parses_versions_without_the_prefix()
    {
        $output = $this->dependenciesOutput([
            'dominikb/composer-license-checker' => [
                'version' => '2.5.0',
                'license' => ['MIT']
            ]
        ]);

        $this->assertDependencyMatches(
            $this->parser->parse($output)[0],
            'dominikb/composer-license-checker',
            '2.5.0',
            'MIT'
        );
    }

    /** @test */
    public function it_handles_dependencies_on_branches()
    {
        $output = $this->dependenciesOutput([
            'dominikb/composer-license-checker' => [
                'version' => 'dev-test 16af31f',
                'license' => ['MIT']
            ]
        ]);

        $this->assertDependencyMatches(
            $this->parser->parse($output)[0],
            'dominikb/composer-license-checker',
            'dev-test 16af31f',
            'MIT'
        );
    }

    /** @test */
    public function it_parses_a_version_without_a_patch_number()
    {
        $output = $this->dependenciesOutput([
            'dominikb/composer-license-checker' => [
                'version' => '2.5',
                'license' => ['MIT']
            ]
        ]);

        $this->assertDependencyMatches(
            $this->parser->parse($output)[0],
            'dominikb/composer-license-checker',
            '2.5',
            'MIT'
        );
    }

    /** @test */
    public function it_parses_multiple_licenses_per_dependency()
    {
        $output = $this->dependenciesOutput([
            'dominikb/composer-license-checker' => [
                'version' => '2.5',
                'license' => ['LGPL-2.1-only', 'GPL-3.0-or-later']
            ]
        ]);

        $this->assertDependencyMatches(
            $this->parser->parse($output)[0],
            'dominikb/composer-license-checker',
            '2.5',
            'LGPL-2.1-only',
            'GPL-3.0-or-later'
        );

    }

    private function dependenciesOutput(array $dependencies) : string {
        return str_replace('<<<DEPENDENCIES>>>', json_encode($dependencies), $this->STUB);
    }

    /**
     * @param  Dependency  $dependency
     * @param  string  $name
     * @param  string  $version
     * @param  string[]  $licenses
     */
    private function assertDependencyMatches(Dependency $dependency, string $name, string $version, ...$licenses)
    {
        $this->assertSame($name, $dependency->getName());
        $this->assertSame($version, $dependency->getVersion());

        $this->assertEquals($licenses, $dependency->getLicenses());
    }
}
