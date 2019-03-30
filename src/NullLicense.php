<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use DateTimeImmutable;

class NullLicense extends License
{
    public function __construct()
    {
        $this->setShortName('-');
        $this->setCan([]);
        $this->setCannot([]);
        $this->setMust([]);
        $this->setSource('-');
        $this->setCreatedAt(new DateTimeImmutable);
    }
}
