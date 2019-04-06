<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Contracts;

use Psr\SimpleCache\CacheInterface;

interface CacheAwareContract
{
    public function setCache(CacheInterface $cache): void;
}
