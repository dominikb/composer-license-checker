<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\Dependency;
use Dominikb\ComposerLicenseChecker\ConstraintViolation;
use Dominikb\ComposerLicenseChecker\ConstraintViolationDetector;

class ConstraintViolationDetectorTest extends TestCase
{
    /** @var ConstraintViolationDetector */
    private $detector;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->detector = new ConstraintViolationDetector;
    }

    /** @test */
    public function given_a_single_license_on_the_blacklist_it_detects_a_violation()
    {
        $this->detector->setBlacklist(['MIT']);

        $dependency = (new Dependency)->setLicenses(['MIT']);

        $violations = $this->detector->detectViolations([$dependency]);

        $this->assertViolationFound($violations);
    }

    /** @test */
    public function given_a_subset_of_blacklisted_licenses_no_violation_is_detected()
    {
        $this->detector->setBlacklist(['MIT']);

        $dependency = (new Dependency)->setLicenses(['MIT', 'BSD']);

        $violations = $this->detector->detectViolations([$dependency]);

        $this->assertViolationNotFound($violations);
    }

    /**
     * @param ConstraintViolation[] $violations
     */
    private function assertViolationFound(array $violations)
    {
        $anyViolationFound = false;

        foreach ($violations as $violation) {
            $anyViolationFound = $anyViolationFound || $violation->hasViolators();
        }

        $this->assertTrue($anyViolationFound, 'At least one violation was expected but no violation was found!');
    }

    /**
     * @param ConstraintViolation[] $violations
     */
    private function assertViolationNotFound(array $violations)
    {
        $anyViolationFound = false;

        foreach ($violations as $violation) {
            $anyViolationFound = $anyViolationFound || $violation->hasViolators();
        }

        $this->assertFalse($anyViolationFound, 'No violations were expected but violations were found!');
    }
}
