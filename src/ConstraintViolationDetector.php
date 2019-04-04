<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use Dominikb\ComposerLicenseChecker\Exceptions\LogicException;
use Dominikb\ComposerLicenseChecker\Contracts\LicenseConstraintHandler;

class ConstraintViolationDetector implements LicenseConstraintHandler
{
    /** @var string[] */
    protected $blacklist = [];

    /** @var string[] */
    protected $whitelist = [];

    public function setBlacklist(array $licenses): void
    {
        $this->blacklist = $licenses;
    }

    public function setWhitelist(array $licenses): void
    {
        $this->whitelist = $licenses;
    }

    /**
     * @param Dependency[] $dependencies
     *
     * @return ConstraintViolation[]
     * @throws LogicException
     */
    public function detectViolations(array $dependencies): array
    {
        $this->ensureConfigurationIsValid();

        return [
            $this->detectBlacklistViolation($dependencies),
            $this->detectWhitelistViolation($dependencies),
        ];
    }

    /**
     * @throws LogicException
     */
    public function ensureConfigurationIsValid(): void
    {
        $overlap = array_intersect($this->blacklist, $this->whitelist);

        if (count($overlap) > 0) {
            $invalidLicenseConditionals = sprintf('"%s"', implode('", "', $overlap));
            throw new LogicException("Licenses must not be black- and whitelisted at the same time: ${invalidLicenseConditionals}");
        }
    }

    /**
     * @param Dependency[] $dependencies
     */
    private function detectBlacklistViolation(array $dependencies): ConstraintViolation
    {
        $violation = new ConstraintViolation('Blacklisted license found!');

        if (! empty($this->blacklist)) {
            foreach ($dependencies as $dependency) {
                if ($this->allLicensesOnList($dependency->getLicenses(), $this->blacklist)) {
                    $violation->add($dependency);
                }
            }
        }

        return $violation;
    }

    /**
     * @param Dependency[] $dependencies
     */
    private function detectWhitelistViolation(array $dependencies): ConstraintViolation
    {
        $violation = new ConstraintViolation('Non white-listed license found!');

        if (! empty($this->whitelist)) {
            foreach ($dependencies as $dependency) {
                if (! $this->anyLicenseOnList($dependency->getLicenses(), $this->whitelist)) {
                    $violation->add($dependency);
                }
            }
        }

        return $violation;
    }

    private function allLicensesOnList(array $licenses, array $list): bool
    {
        return count(array_intersect($licenses, $list)) === count($licenses);
    }

    private function anyLicenseOnList(array $licenses, array $list): bool
    {
        return count(array_intersect($licenses, $list)) > 0;
    }
}
