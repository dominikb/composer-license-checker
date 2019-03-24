<?php

namespace Dominikb\ComposerLicenseChecker;

use DateTimeImmutable;
use GuzzleHttp\Client;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class ComposerLicenseChecker
{
    const LINES_BEFORE_DEPENDENCY_VERSIONS = 2;

    /** @var string */
    protected $composerPath;
    /** @var OutputInterface */
    protected $output;

    public function __construct(string $composerPath, OutputInterface $output = null)
    {
        $this->composerPath = $composerPath;
        $this->output = $output ?? new ConsoleOutput;
    }

    public function check(string $path)
    {
        exec("$this->composerPath -d $path licenses", $output);

        $filteredOutput = $this->filterHeaderOutput($output);

        $dependencies = $this->splitColumnsIntoDependencies($filteredOutput);

        $groupedByLicense = $this->groupDependenciesByLicense($dependencies);

        $licenses = $this->lookUpLicenses(array_keys($groupedByLicense));

        $dependencyTable = new Table($this->output);
        $dependencyTable->setHeaders(['license', 'count']);
        foreach ($groupedByLicense as $license => $items) {
            $dependencyTable->addRow(['license' => $license, 'count' => count($items)]);
        }
        $dependencyTable->render();

        /** @var License $license */
        foreach ($licenses as $license) {
            $headline = sprintf("\nLicense: %s (%s)", $license->getShortName(), $license->getSource());
            $this->output->writeln($headline);
            $licenseTable = new Table($this->output);
            $licenseTable->setHeaders(['CAN', 'CAN NOT', 'MUST']);

            $can = $license->getCan();
            $cannot = $license->getCannot();
            $must = $license->getMust();
            $count = max(count($can), count($cannot), count($must));

            $can = array_pad($can, $count, null);
            $cannot = array_pad($cannot, $count, null);
            $must = array_pad($must, $count, null);

            $inlineHeading = function ($key, $value) {
                if ( ! is_string($key)) {
                    return "";
                }

                return wordwrap($key, 60);
//                return wordwrap("$key\n($value)", 60);
            };

            $can = array_map_keys($can, $inlineHeading);
            $cannot = array_map_keys($cannot, $inlineHeading);
            $must = array_map_keys($must, $inlineHeading);

            for ($i = 0; $i < $count; $i++) {
                $licenseTable->addRow([
                    'CAN'    => $can[$i],
                    'CANNOT' => $cannot[$i],
                    'MUST'   => $must[$i],
                ]);
            }
            $licenseTable->render();
        }

        die;
    }

    private function filterHeaderOutput(array $output): array
    {
        for ($i = 0; $i < count($output); $i++) {
            if ($output[$i] === "") {
                return array_slice($output, $i + self::LINES_BEFORE_DEPENDENCY_VERSIONS);
            }
        }

        throw ParsingException::reason("Could not filter out headers");
    }

    private function splitColumnsIntoDependencies(array $output): array
    {
        $mappedToObjects = [];
        foreach ($output as $dependency) {
            $normalized = preg_replace("/\\s+/", " ", $dependency);
            $columns = explode(" ", $normalized);
            $mappedToObjects[] = (new Dependency)
                ->setName($columns[0])
                ->setVersion($columns[1])
                ->setLicense($columns[2]);
        }

        return $mappedToObjects;
    }

    private function groupDependenciesByLicense(array $dependencies)
    {
        $grouped = [];

        foreach ($dependencies as $dependency) {
            if ( ! isset($grouped[$license = $dependency->getLicense()])) {
                $grouped[$license] = [];
            }
            $grouped[$license][] = $dependency;
        }

        return $grouped;
    }

    private function lookUpLicenses(array $licenses)
    {
        $lookedUp = [];
        foreach ($licenses as $license) {
            $lookedUp[$license] = $this->searchForLicenseInformation($license);
        }

        return $lookedUp;
    }

    private function searchForLicenseInformation($license)
    {
        $baseUrl = "https://tldrlegal.com";
        $url = "$baseUrl/search?q=$license";

        $client = new Client;

        $res = $client->get($url);

        $crawler = new Crawler($res->getBody()->getContents());

        $element = $crawler->filter('div#licenses > .search-result > a')->first();

        $link = $element->attr('href');

        $licenceUrl = "$baseUrl$link";

        $res = $client->get($licenceUrl);

        $html = $res->getBody()->getContents();

        return (new License)
            ->setShortName($license)
            ->setCan($this->extractCans($html))
            ->setCannot($this->extractCannots($html))
            ->setMust($this->extractMusts($html))
            ->setSource($licenceUrl)
            ->setCreatedAt(new DateTimeImmutable);
    }

    private function extractCans($html)
    {
        return $this->extractListByColor($html, 'green');
    }

    private function extractCannots($html)
    {
        return $this->extractListByColor($html, 'red');
    }

    private function extractMusts($html)
    {
        return $this->extractListByColor($html, 'blue');
    }

    private function extractListByColor($html, $color)
    {
        $crawler = new Crawler($html);

        $headings = $crawler->filter(".bucket-list.$color li div.attr-head")
            ->each(function (Crawler $crawler) {
                return $crawler->getNode(0)->textContent;
            });

        $bodies = $crawler->filter(".bucket-list.$color li div.attr-body")
            ->each(function (Crawler $crawler) {
                return $crawler->getNode(0)->textContent;
            });

        return array_combine($headings, $bodies);
    }
}
