<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Test\TestCase\Provider;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TheMusicDev\Analytics\Provider\GoogleProvider;

final class GoogleProviderTest extends TestCase
{
    public function testAWellFormedMeasurementIdIsEnabledAndRendersTheGtagSnippet(): void
    {
        $provider = new GoogleProvider();
        $config = ['measurementId' => 'G-L6M06JFS3F'];

        $this->assertTrue($provider->isEnabled($config));
        $tag = $provider->tag($config);
        $this->assertStringContainsString('<script async src="https://www.googletagmanager.com/gtag/js?id=G-L6M06JFS3F"></script>', $tag);
        $this->assertStringContainsString("gtag('config','G-L6M06JFS3F')", $tag);
        $this->assertStringContainsString('window.dataLayer', $tag);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function badIds(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'not a string' => [123],
            'wrong prefix' => ['UA-12345-1'],
            'lower case' => ['g-l6m06jfs3f'],
            'too short' => ['G-AB'],
            'quote break-out' => ["G-AB12'); alert(1);//"],
            'tag break-out' => ['G-AB12</script><script>alert(1)</script>'],
        ];
    }

    #[DataProvider('badIds')]
    public function testAMissingOrMalformedIdTurnsTheProviderOff(mixed $id): void
    {
        $this->assertFalse((new GoogleProvider())->isEnabled(['measurementId' => $id]));
    }

    public function testNoKeyAtAllIsOff(): void
    {
        $this->assertFalse((new GoogleProvider())->isEnabled([]));
    }
}
