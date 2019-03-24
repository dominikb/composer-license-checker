<?php declare(strict_types = 1);

use Dominikb\ComposerLicenseChecker\ComposerLicenseChecker;

require_once __DIR__ . '/../vendor/autoload.php';

$checker = new ComposerLicenseChecker(realpath('./vendor/bin/composer'));

$checker->check(realpath('.'));
