<?php declare(strict_types = 1);


namespace Dominikb\ComposerLicenseChecker;


class Dependency
{
    /** @var string */
    private $name;

    /** @var string */
    private $version;

    /** @var string */
    private $license;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Dependency
     */
    public function setName(string $name): Dependency
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
     *
     * @return Dependency
     */
    public function setVersion(string $version): Dependency
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * @param string $license
     *
     * @return Dependency
     */
    public function setLicense(string $license): Dependency
    {
        $this->license = $license;

        return $this;
    }
}
