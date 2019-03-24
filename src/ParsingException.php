<?php declare(strict_types = 1);


namespace Dominikb\ComposerLicenseChecker;


use Exception;

class ParsingException extends Exception
{

    public static function reason(string $string): self
    {
        return new self($string);
    }
}
