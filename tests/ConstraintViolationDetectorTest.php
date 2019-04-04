<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\Dependency;
use Dominikb\ComposerLicenseChecker\ConstraintViolation;
use Dominikb\ComposerLicenseChecker\Exceptions\LogicException;
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
    public function it_throws_a_logic_exception_given_an_overlap_between_black_and_whitelist()
    {
        $this->detector->setWhitelist(['MIT', 'other-license']);
        $this->detector->setBlacklist(['MIT', 'another-license']);

        $this->expectException(LogicException::class);
        $this->detector->detectViolations([]);
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

    /** @test */
    public function given_a_non_white_listed_license_a_violation_is_detected()
    {
        $this->detector->setWhitelist(['MIT']);

        $dependency = (new Dependency)->setLicenses(['not-white-listed']);

        $this->assertViolationFound(
            $this->detector->detectViolations([$dependency])
        );
    }

    /** @test */
    public function given_at_least_one_whitelisted_licenses_no_violation_is_detected()
    {
        $this->detector->setWhitelist(['MIT']);

        $dependency = (new Dependency)->setLicenses(['violation', 'MIT']);

        $this->assertViolationNotFound(
            $this->detector->detectViolations([$dependency])
        );
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
