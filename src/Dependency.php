<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

class Dependency
{
    /** @var string */
    private $name;

    /** @var string */
    private $version;

    /** @var string[] */
    private $licenses;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLicenses(): array
    {
        return $this->licenses;
    }

    /**
     * @param string[] $licenses
     */
    public function setLicenses(array $licenses): self
    {
        $this->licenses = $licenses;

        return $this;
    }
}
