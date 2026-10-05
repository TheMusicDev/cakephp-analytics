<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use TheMusicDev\Analytics\Lib\TagRenderer;

/**
 * What the layout prints: host gating, provider tags, injections and their order, and the validation of both.
 */
final class TagRendererTest extends TestCase
{
    private const UMAMI_ID = '0b3a1c52-9d7e-4f10-8a6b-2c4d5e6f7081';

    /**
     * A full config for the allowed host `example.test`.
     *
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function config(array $extra = []): array
    {
        return $extra + [
            'hosts' => ['example.test'],
            'tracking' => [
                'google' => ['measurementId' => 'G-L6M06JFS3F'],
                'umami' => ['websiteId' => self::UMAMI_ID, 'src' => 'https://u.example.test/script.js'],
            ],
            'inject' => [],
        ];
    }

    public function testNothingRendersOnAHostThatIsNotListed(): void
    {
        $config = $this->config(['inject' => [['position' => 'body-end', 'html' => '<b>x</b>']]]);

        $this->assertSame('', TagRenderer::head($config, 'staging.example.test'));
        $this->assertSame('', TagRenderer::bodyEnd($config, 'staging.example.test'));
        $this->assertSame('', TagRenderer::head($config, 'localhost'));
        $this->assertSame('', TagRenderer::head($config, ''));
    }

    public function testNothingRendersWhenNoHostIsListed(): void
    {
        $this->assertSame('', TagRenderer::head($this->config(['hosts' => []]), 'example.test'));
        $this->assertSame('', TagRenderer::head(['tracking' => $this->config()['tracking']], 'example.test'));
    }

    public function testTheHostComparisonIgnoresCase(): void
    {
        $this->assertNotSame('', TagRenderer::head($this->config(['hosts' => ['Example.Test']]), 'EXAMPLE.test'));
    }

    public function testEveryEnabledProviderRendersInConfigOrder(): void
    {
        $head = TagRenderer::head($this->config(), 'example.test');

        $this->assertStringContainsString('gtag/js?id=G-L6M06JFS3F', $head);
        $this->assertStringContainsString('data-website-id="' . self::UMAMI_ID . '"', $head);
        $this->assertLessThan(strpos($head, 'data-website-id'), strpos($head, 'gtag/js'), 'google is listed first');
    }

    public function testAProviderWithoutIdsRendersNothingAndOthersStillDo(): void
    {
        $config = $this->config();
        $config['tracking']['google'] = ['measurementId' => null];

        $head = TagRenderer::head($config, 'example.test');

        $this->assertStringNotContainsString('gtag', $head);
        $this->assertStringContainsString('data-website-id', $head);
    }

    public function testAProviderSetToFalseOrNullIsOff(): void
    {
        foreach ([false, null] as $off) {
            $config = $this->config();
            $config['tracking']['google'] = $off;

            $head = TagRenderer::head($config, 'example.test');

            $this->assertStringNotContainsString('gtag', $head);
            $this->assertStringContainsString('data-website-id', $head, 'the other provider still renders');
        }
    }

    public function testAMalformedProviderIdIsNotAnError(): void
    {
        $config = $this->config();
        $config['tracking']['google'] = ['measurementId' => "G-AB'); alert(1)//"];

        $this->assertStringNotContainsString('alert', TagRenderer::head($config, 'example.test'));
    }

    public function testInjectionsAreSortedByOrderWithTiesKeepingConfigOrder(): void
    {
        $config = $this->config(['tracking' => [], 'inject' => [
            ['order' => 20, 'html' => '<i>c</i>'],
            ['order' => 10, 'html' => '<i>a</i>'],
            ['html' => '<i>z</i>'],
            ['order' => 10, 'html' => '<i>b</i>'],
        ]]);

        $this->assertSame("<i>z</i>\n<i>a</i>\n<i>b</i>\n<i>c</i>\n", TagRenderer::head($config, 'example.test'));
    }

    public function testBodyEndInjectionsAreSeparateFromHeadOnes(): void
    {
        $config = $this->config(['tracking' => [], 'inject' => [
            ['position' => 'body-end', 'html' => '<i>end</i>'],
            ['position' => 'head', 'html' => '<i>head</i>'],
        ]]);

        $this->assertSame("<i>head</i>\n", TagRenderer::head($config, 'example.test'));
        $this->assertSame("<i>end</i>\n", TagRenderer::bodyEnd($config, 'example.test'));
    }

    public function testAHeadInjectionComesBeforeTheTrackingTags(): void
    {
        $config = $this->config(['inject' => [['position' => 'head', 'html' => '<script>/* first */</script>']]]);

        $head = TagRenderer::head($config, 'example.test');

        $this->assertLessThan(strpos($head, 'gtag/js'), strpos($head, '/* first */'), 'a head injection renders before the tags');
    }

    public function testAScriptUrlBecomesAnEscapedScriptTagWithItsAttributes(): void
    {
        $config = $this->config(['tracking' => [], 'inject' => [
            ['src' => 'https://c.example.test/cmp.js?a=1&b=2', 'async' => true, 'defer' => true],
            ['src' => 'https://c.example.test/plain.js'],
        ]]);

        $this->assertSame(
            '<script async defer src="https://c.example.test/cmp.js?a=1&amp;b=2"></script>' . "\n"
            . '<script src="https://c.example.test/plain.js"></script>' . "\n",
            TagRenderer::head($config, 'example.test'),
        );
    }

    public function testInlineHtmlIsOutputAsWritten(): void
    {
        $config = $this->config(['tracking' => [], 'inject' => [['html' => '<script>var a = "<b>";</script>']]]);

        $this->assertSame('<script>var a = "<b>";</script>' . "\n", TagRenderer::head($config, 'example.test'));
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function badInjections(): array
    {
        return [
            'not an array' => ['<script></script>', 'must be an array'],
            'neither html nor src' => [['position' => 'head'], "exactly one of 'html' or 'src'"],
            'both html and src' => [['html' => 'x', 'src' => 'https://a.test/x.js'], "exactly one of 'html' or 'src'"],
            'empty html' => [['html' => ''], "exactly one of 'html' or 'src'"],
            'bad position' => [['position' => 'footer', 'html' => 'x'], 'position must be one of'],
            'javascript url' => [['src' => 'javascript:alert(1)'], 'must be an http(s) URL'],
            'relative url' => [['src' => '/cmp.js'], 'must be an http(s) URL'],
        ];
    }

    #[DataProvider('badInjections')]
    public function testAMalformedInjectionThrows(mixed $entry, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        TagRenderer::head($this->config(['inject' => [$entry]]), 'example.test');
    }

    public function testAMalformedInjectionThrowsEvenOnAHostThatRendersNothing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TagRenderer::head($this->config(['inject' => [['position' => 'nope', 'html' => 'x']]]), 'localhost');
    }

    public function testAMalformedInjectionThrowsFromBodyEndToo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TagRenderer::bodyEnd($this->config(['inject' => [['html' => '']]]), 'example.test');
    }

    public function testAnUnknownTrackingProviderThrowsEvenOffHost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown Analytics tracking provider 'gogle' (known: google, umami)");

        TagRenderer::head($this->config(['tracking' => ['gogle' => []]]), 'localhost');
    }
}
