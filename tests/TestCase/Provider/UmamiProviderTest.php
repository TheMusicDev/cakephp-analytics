<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Test\TestCase\Provider;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TheMusicDev\Analytics\Provider\UmamiProvider;

final class UmamiProviderTest extends TestCase
{
    private const ID = '0b3a1c52-9d7e-4f10-8a6b-2c4d5e6f7081';

    public function testAWebsiteIdAndAnHttpsScriptUrlRenderTheUmamiTag(): void
    {
        $provider = new UmamiProvider();
        $config = ['websiteId' => self::ID, 'src' => 'https://analytics.example.test/script.js'];

        $this->assertTrue($provider->isEnabled($config));
        $this->assertSame(
            '<script defer src="https://analytics.example.test/script.js" data-website-id="' . self::ID . '"></script>' . "\n",
            $provider->tag($config),
        );
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function badConfigs(): array
    {
        $src = 'https://analytics.example.test/script.js';

        return [
            'nothing' => [[]],
            'no src' => [['websiteId' => self::ID]],
            'no id' => [['src' => $src]],
            'not a uuid' => [['websiteId' => 'abc', 'src' => $src]],
            'plain http' => [['websiteId' => self::ID, 'src' => 'http://analytics.example.test/script.js']],
            'javascript url' => [['websiteId' => self::ID, 'src' => 'javascript:alert(1)']],
            'not a url' => [['websiteId' => self::ID, 'src' => 'script.js']],
            'not strings' => [['websiteId' => 1, 'src' => ['x']]],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('badConfigs')]
    public function testAMissingOrMalformedValueTurnsTheProviderOff(array $config): void
    {
        $this->assertFalse((new UmamiProvider())->isEnabled($config));
    }

    public function testTheScriptUrlIsHtmlEscaped(): void
    {
        $tag = (new UmamiProvider())->tag(['websiteId' => self::ID, 'src' => 'https://a.example.test/s.js?x=1&y=2']);

        $this->assertStringContainsString('src="https://a.example.test/s.js?x=1&amp;y=2"', $tag);
    }
}
