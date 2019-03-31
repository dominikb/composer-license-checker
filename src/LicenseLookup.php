<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker;

use DateTimeImmutable;
use InvalidArgumentException;
use GuzzleHttp\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Dominikb\ComposerLicenseChecker\Contracts\LicenseLookup as LicenseLookupContract;

class LicenseLookup implements LicenseLookupContract
{
    const API_HOST = 'https://tldrlegal.com';

    /** @var ClientInterface */
    protected $http;
    /** @var CacheInterface */
    protected $cache;

    public function __construct(ClientInterface $http, CacheInterface $cache = null)
    {
        $this->http = $http;
        $this->cache = $cache ?? new FilesystemCache('LicenseLookup', 3600, __DIR__.'/../.cache');
    }

    public function lookUp(string $licenseName): License
    {
        if ($cached = $this->cache->get($licenseName)) {
            return $cached;
        }

        try {
            $detailsPageUrl = $this->queryForDetailPageUrl($licenseName);

            $license = $this->resolveLicenseInformation($licenseName, $detailsPageUrl);
        } catch (InvalidArgumentException $exception) {
            $license = new NullLicense;
        }

        $this->cache->set($licenseName, $license);

        return $license;
    }

    private function queryForDetailPageUrl(string $licenseShortName): string
    {
        $searchUrl = sprintf('%s/search?q=%s', static::API_HOST, $licenseShortName);

        $response = $this->http->request('get', $searchUrl);

        $crawler = $this->makeCrawler($response->getBody()->getContents());

        $relativeUrl = $crawler
            ->filter('div#licenses > .search-result > a')
            ->first()
            ->attr('href');

        return static::API_HOST.$relativeUrl;
    }

    private function makeCrawler(string $html): Crawler
    {
        return new Crawler($html);
    }

    private function resolveLicenseInformation(string $licenseShortName, string $detailsPageUrl): License
    {
        $response = $this->http->request('get', $detailsPageUrl);
        $pageContent = $response->getBody()->getContents();

        $crawler = $this->makeCrawler($pageContent);

        $license = (new License)
            ->setShortName($licenseShortName)
            ->setCan($this->extractCans($crawler))
            ->setCannot($this->extractCannots($crawler))
            ->setMust($this->extractMusts($crawler))
            ->setSource($detailsPageUrl)
            ->setCreatedAt(new DateTimeImmutable);

        return $license;
    }

    private function extractCans(Crawler $crawler)
    {
        return $this->extractListByColor($crawler, 'green');
    }

    private function extractCannots(Crawler $crawler)
    {
        return $this->extractListByColor($crawler, 'red');
    }

    private function extractMusts(Crawler $crawler)
    {
        return $this->extractListByColor($crawler, 'blue');
    }

    private function extractListByColor(Crawler $crawler, $color)
    {
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
