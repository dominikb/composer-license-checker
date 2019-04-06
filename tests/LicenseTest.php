<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use DateTime;
use Dominikb\ComposerLicenseChecker\License;

class LicenseTest extends TestCase
{
    /** @test */
    public function getters_return_the_values_of_instance_properties()
    {
        $license = (new License('shortName'))
            ->setSource('https://dominikb.io')
            ->setCan(['something'])
            ->setCannot(['other thing'])
            ->setMust(['absolutely need to'])
            ->setCreatedAt($date = new DateTime);

        $this->assertSame('shortName', $license->getShortName());
        $this->assertSame('https://dominikb.io', $license->getSource());
        $this->assertSame(['something'], $license->getCan());
        $this->assertSame(['other thing'], $license->getCannot());
        $this->assertSame(['absolutely need to'], $license->getMust());
        $this->assertSame($date, $license->getCreatedAt());
    }
}
