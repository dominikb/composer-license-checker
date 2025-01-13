<?php

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\Dependency;
use PHPUnit\Framework\TestCase;

class DependencyTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_none_as_default_license_when_no_license_is_provided()
    {
        $dependency = new Dependency('name', 'version');

        $this->assertSame(Dependency::NO_LICENSES, $dependency->getLicenses());
    }
}
