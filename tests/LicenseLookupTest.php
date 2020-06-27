<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use Dominikb\ComposerLicenseChecker\License;
use Dominikb\ComposerLicenseChecker\LicenseLookup;
use Dominikb\ComposerLicenseChecker\NoLookupLicenses;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Cache\Simple\NullCache;

class LicenseLookupTest extends TestCase
{
    /**
     * When looking up the Apache-2.0 license we actually do not want the first result.
     * This test asserts, that not necessarily the first link will be followed up on.
     *
     * @test
     */
    public function it_fuzzy_matches_the_best_link_to_detail_pages()
    {
        $handler = new MockHandler([
            new Response(200, [], fopen(__DIR__.'/apache-2.0-search.html', 'r')),
            new Response(),
        ]);
        $client = new Client(['handler' => HandlerStack::create($handler)]);

        $lookup = new LicenseLookup($client, new NullCache);

        $res = $lookup->lookUp('Apache-2.0');

        $this->assertEquals('https://tldrlegal.com/license/apache-license-2.0-(apache-2.0)', $res->getSource());
    }

    /** @test */
    public function given_invalid_license_names_empty_license_objects_get_returned()
    {
        $handler = new MockHandler([]);
        $client = new Client(['handler' => HandlerStack::create($handler)]);
        $lookup = new LicenseLookup($client, new NullCache);

        $resultForNone = $lookup->lookUp('none');
        $resultForProprietary = $lookup->lookUp('proprietary');

        $this->assertInstanceOf(License::class, $resultForNone);
        $this->assertInstanceOf(License::class, $resultForProprietary);

        $this->assertSame('-', $resultForNone->getSource());
        $this->assertSame('-', $resultForProprietary->getSource());
    }

    /** @test */
    public function given_an_error_when_looking_up_detail_information_it_returns_a_no_lookup_license()
    {
        $handler = new MockHandler([
            new Response(200, [], fopen(__DIR__.'/apache-2.0-search.html', 'r')),
            new Response(500),
        ]);
        $client = new Client(['handler' => HandlerStack::create($handler)]);
        $lookup = new LicenseLookup($client, new NullCache);

        $license = $lookup->lookUp('Apache-2.0');

        $this->assertInstanceOf(NoLookupLicenses::class, $license);
    }

    /** @test */
    public function given_an_error_when_searching_for_a_details_page_it_returns_a_no_lookup_license()
    {
        $handler = new MockHandler([
            new Response(500),
        ]);
        $client = new Client(['handler' => HandlerStack::create($handler)]);
        $lookup = new LicenseLookup($client, new NullCache);

        $license = $lookup->lookUp('Apache-2.0');

        $this->assertInstanceOf(NoLookupLicenses::class, $license);
    }
}
