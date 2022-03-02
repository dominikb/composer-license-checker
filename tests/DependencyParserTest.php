<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\Dependency;
use Dominikb\ComposerLicenseChecker\DependencyParser;

class DependencyParserTest extends TestCase
{
    /** @var DependencyParser */
    private $parser;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->parser = new DependencyParser;
    }

    /** @test */
    public function it_parses_a_simple_dependency()
    {
        $this->assertDependencyMatches(
            $this->parser->parse('dominikb/composer-license-checker v1.0.0 MIT'),
            'dominikb/composer-license-checker',
            'v1.0.0',
            'MIT'
        );
    }

    /** @test */
    public function it_parses_versions_without_the_prefix()
    {
        $this->assertDependencyMatches(
            $this->parser->parse('dominikb/composer-license-checker 1.0.0 MIT'),
            'dominikb/composer-license-checker',
            '1.0.0',
            'MIT'
        );
    }

    /** @test */
    public function it_handles_dependencies_on_branches()
    {
        $this->assertDependencyMatches(
            $this->parser->parse('dominikb/composer-license-checker dev-master f12f12 MIT'),
            'dominikb/composer-license-checker',
            'dev-master - f12f12',
            'MIT'
        );
    }

    /** @test */
    public function it_parses_a_version_without_a_patch_number()
    {
        $this->assertDependencyMatches(
            $this->parser->parse('dominikb/composer-license-checker 1.0 MIT'),
            'dominikb/composer-license-checker',
            '1.0',
            'MIT'
        );
    }

    /** @test */
    public function it_parses_comma_separated_dependencies()
    {
        $this->assertDependencyMatches(
            $this->parser->parse('dominikb/composer-license-checker v1.0.0 MIT, BSD-3-Clause'),
            'dominikb/composer-license-checker',
            'v1.0.0',
            'MIT', 'BSD-3-Clause'
        );
    }

    /** @test */
    public function it_parses_multiple_dependencies_within_brackets()
    {
        $this->assertDependencyMatches(
            $this->parser->parse('dominikb/composer-license-checker v1.0 (MIT, BSD)'),
            'dominikb/composer-license-checker',
            'v1.0',
            'MIT',
            'BSD'
        );
    }

    /** @test */
    public function it_parses_multiple_dependencies_within_brackets_split_by_or_keyword()
    {
        $this->assertDependencyMatches(
            $this->parser->parse('dominikb/composer-license-checker v1.0.0 (MIT or BSD-3-Clause)'),
            'dominikb/composer-license-checker',
            'v1.0.0',
            'MIT',
            'BSD-3-Clause'
        );
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
