<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use PHPUnit\Framework\Attributes\Test;
use Dominikb\ComposerLicenseChecker\ConstraintViolation;
use Dominikb\ComposerLicenseChecker\Dependency;

class ConstraintViolationTest extends TestCase
{
    #[Test]
    public function getters_return_the_instance_values()
    {
        $violation = new ConstraintViolation('title');

        $violation->add($dependency = new Dependency);

        $this->assertSame('title', $violation->getTitle());
        $this->assertSame([$dependency], $violation->getViolators());
    }
}
