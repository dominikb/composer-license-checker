<?php

declare(strict_types=1);

namespace Dominikb\ComposerLicenseChecker\Tests;

use PHPUnit\Framework\Attributes\Test;

class HelpersTest extends TestCase
{
    #[Test]
    public function the_callback_gets_called_with_keys_and_values()
    {
        $array = ['key' => 'value'];

        $mapped = array_map_keys($array, function ($key, $value) {
            $this->assertSame('key', $key);
            $this->assertSame('value', $value);

            return $key.$value;
        });

        $this->assertCount(1, $mapped);
        $this->assertSame([0 => 'keyvalue'], $mapped);
    }
}
