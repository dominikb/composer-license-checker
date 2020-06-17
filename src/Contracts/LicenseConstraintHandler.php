<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Contracts;

use Dominikb\ComposerLicenseChecker\Dependency;
use Dominikb\ComposerLicenseChecker\ConstraintViolation;

interface LicenseConstraintHandler
{
    public function setBlocklist(array $licenses): void;

    public function setAllowlist(array $licenses): void;

    /**
     * @param Dependency[] $dependencies
     *
     * @return ConstraintViolation[]
     */
    public function detectViolations(array $dependencies): array;
}
