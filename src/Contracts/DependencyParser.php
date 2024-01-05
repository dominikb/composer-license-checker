<?php

namespace Dominikb\ComposerLicenseChecker\Contracts;

use Dominikb\ComposerLicenseChecker\Dependency;

interface DependencyParser
{
    /**
     * @return Dependency[]
     */
    public function parse(string $dependencyOutput): array;
}
