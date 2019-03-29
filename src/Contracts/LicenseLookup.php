<?php declare(strict_types = 1);


namespace Dominikb\ComposerLicenseChecker\Contracts;


use Dominikb\ComposerLicenseChecker\License;

interface LicenseLookup
{
    public function lookUp(string $licenseShortName): License;
}
