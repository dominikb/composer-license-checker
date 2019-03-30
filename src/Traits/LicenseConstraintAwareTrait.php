<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Traits;

use Dominikb\ComposerLicenseChecker\Contracts\LicenseConstraintHandler;

trait LicenseConstraintAwareTrait
{
    /** @var LicenseConstraintHandler */
    protected $licenseConstraintHandler;

    public function setLicenseConstraintHandler(LicenseConstraintHandler $handler): void
    {
        $this->licenseConstraintHandler = $handler;
    }

    private function shouldHandleConstraints(): bool
    {
        return isset($this->licenseConstraintHandler);
    }
}
