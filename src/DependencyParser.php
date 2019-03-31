<?php declare(strict_types = 1);


namespace Dominikb\ComposerLicenseChecker;


class DependencyParser
{
    const REPEATED_WHITESPACE               = '/\\s+/';
    const EVERYTHING_BEFORE_FIRST_SPACE     = '/^[^ ]* /';
    const SEMVER                            = '/^v?\d+\.\d+(\.\d+)?(-[\w\d]*)?/';
    const ROUND_BRACKETS                    = '(\(|\))';
    const SPACE_WITH_OPTIONAL_LEADING_COMMA = '/,?\s/';

    public function parse(string $row): Dependency
    {
        $row = $this->removeRepeatedWhitespace($row);

        [$name, $rest] = $this->extractDependencyName($row);

        [$version, $rest] = $this->extractDependencyVersion($rest);

        $licenses = $this->extractLicenses($rest);

        return (new Dependency)
            ->setName($name)
            ->setVersion($version)
            ->setLicenses($licenses)
            ;
    }

    private function extractDependencyName(string $row): array
    {
        [$name] = explode(' ', $row);

        return [$name, preg_replace(self::EVERYTHING_BEFORE_FIRST_SPACE, '', $row)];
    }

    private function removeRepeatedWhitespace(string $row): string
    {
        return preg_replace(self::REPEATED_WHITESPACE, ' ', $row);
    }

    private function extractDependencyVersion(string $row): array
    {
        if ($version = $this->dependsOnVersion($row)) {
            return [$version, preg_replace(self::EVERYTHING_BEFORE_FIRST_SPACE, '', $row)];
        }

        // If the version does not match on the semver regex, we expect a branch
        // Extract the branch name and commit hash

        $parts = explode(' ', $row);

        [$branchName, $commit] = $parts;

        return ["$branchName - $commit", join(' ', array_slice($parts, 2))];
    }

    private function dependsOnVersion(string $row): ?string
    {
        preg_match(self::SEMVER, $row, $matches);

        return isset($matches[0]) ? $matches[0] : null;
    }

    private function extractLicenses($rest)
    {
        $sanitized = preg_replace('/ or /', ' ', $rest);
        $sanitized = preg_replace(self::ROUND_BRACKETS, '', $sanitized);
        $sanitized = preg_replace(self::SPACE_WITH_OPTIONAL_LEADING_COMMA, ', ', $sanitized);

        return explode(', ', $sanitized);
    }
}
