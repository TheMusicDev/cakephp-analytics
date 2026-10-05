<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Lib;

use InvalidArgumentException;
use TheMusicDev\Analytics\Provider\GoogleProvider;
use TheMusicDev\Analytics\Provider\UmamiProvider;

/**
 * Renders the HTML the layout prints, from the `Analytics` config and the request host (design doc D1 to D9).
 *
 * - Nothing is rendered unless the host is in `hosts` (staging and localhost stay out).
 * - `head()` is the injections with position `head`, sorted by `order`, then the tag of every enabled provider.
 * - `bodyEnd()` is the injections with position `body-end`.
 * - The plugin enforces no consent rule.
 *
 * Injections are code in the host's config, so a malformed one throws, and it does so before the host check:
 * a typo is caught in development, not first on the production site. Provider IDs usually come from the
 * environment, so a bad one only turns that provider off.
 */
final class TagRenderer
{
    private const POSITIONS = ['head', 'body-end'];

    /**
     * Provider key => class.
     *
     * @var array<string, class-string<\TheMusicDev\Analytics\Provider\ProviderInterface>>
     */
    private const PROVIDERS = [
        'google' => GoogleProvider::class,
        'umami' => UmamiProvider::class,
    ];

    /**
     * What goes in the page `<head>`.
     *
     * @param array<array-key, mixed> $config The `Analytics` config block.
     * @throws \InvalidArgumentException On a malformed injection or an unknown tracking provider.
     */
    public static function head(array $config, string $host): string
    {
        $injections = self::injections($config);
        $providers = self::providers($config);
        if (!self::allowed($config, $host)) {
            return '';
        }

        $html = self::render($injections['head']);
        foreach ($providers as [$provider, $providerConfig]) {
            if ($provider->isEnabled($providerConfig)) {
                $html .= $provider->tag($providerConfig);
            }
        }

        return $html;
    }

    /**
     * What goes just before `</body>`.
     *
     * @param array<array-key, mixed> $config The `Analytics` config block.
     * @throws \InvalidArgumentException On a malformed injection or an unknown tracking provider.
     */
    public static function bodyEnd(array $config, string $host): string
    {
        $injections = self::injections($config);
        self::providers($config);

        return self::allowed($config, $host) ? self::render($injections['body-end']) : '';
    }

    /**
     * Whether this host may render anything.
     *
     * @param array<array-key, mixed> $config
     */
    private static function allowed(array $config, string $host): bool
    {
        $hosts = array_map(
            static fn(mixed $allowed): string => strtolower((string)$allowed),
            (array)($config['hosts'] ?? []),
        );

        return $host !== '' && in_array(strtolower($host), $hosts, true);
    }

    /**
     * The tracking providers named in the config, each with its block.
     *
     * @param array<array-key, mixed> $config
     * @return list<array{0: \TheMusicDev\Analytics\Provider\ProviderInterface, 1: array<array-key, mixed>}>
     */
    private static function providers(array $config): array
    {
        $out = [];
        foreach ((array)($config['tracking'] ?? []) as $key => $providerConfig) {
            $class = self::PROVIDERS[(string)$key] ?? null;
            if ($class === null) {
                throw new InvalidArgumentException(sprintf(
                    "Unknown Analytics tracking provider '%s' (known: %s).",
                    (string)$key,
                    implode(', ', array_keys(self::PROVIDERS)),
                ));
            }
            $out[] = [new $class(), (array)$providerConfig];
        }

        return $out;
    }

    /**
     * The validated injections, grouped by position and sorted by `order` (ties keep their config order).
     *
     * @param array<array-key, mixed> $config
     * @return array{head: list<string>, 'body-end': list<string>}
     */
    private static function injections(array $config): array
    {
        $entries = [];
        foreach (array_values((array)($config['inject'] ?? [])) as $index => $entry) {
            $entries[] = self::entry($index, $entry);
        }
        usort(
            $entries,
            static fn(array $a, array $b): int => [$a['order'], $a['index']] <=> [$b['order'], $b['index']],
        );

        $head = [];
        $bodyEnd = [];
        foreach ($entries as $entry) {
            if ($entry['position'] === 'head') {
                $head[] = $entry['html'];
            } else {
                $bodyEnd[] = $entry['html'];
            }
        }

        return ['head' => $head, 'body-end' => $bodyEnd];
    }

    /**
     * Validate one injection and turn it into HTML.
     *
     * @return array{index: int, order: int, position: string, html: string}
     */
    private static function entry(int $index, mixed $entry): array
    {
        if (!is_array($entry)) {
            throw new InvalidArgumentException("Analytics.inject[{$index}] must be an array.");
        }
        $position = $entry['position'] ?? 'head';
        if (!is_string($position) || !in_array($position, self::POSITIONS, true)) {
            throw new InvalidArgumentException(
                "Analytics.inject[{$index}].position must be one of: " . implode(', ', self::POSITIONS) . '.',
            );
        }
        $hasHtml = isset($entry['html']) && is_string($entry['html']) && $entry['html'] !== '';
        $hasSrc = isset($entry['src']) && is_string($entry['src']) && $entry['src'] !== '';
        if ($hasHtml === $hasSrc) {
            throw new InvalidArgumentException("Analytics.inject[{$index}] needs exactly one of 'html' or 'src'.");
        }

        if ($hasHtml) {
            $html = (string)$entry['html'] . "\n";
        } else {
            $src = (string)$entry['src'];
            $scheme = (string)parse_url($src, PHP_URL_SCHEME);
            if (filter_var($src, FILTER_VALIDATE_URL) === false || !in_array($scheme, ['https', 'http'], true)) {
                throw new InvalidArgumentException("Analytics.inject[{$index}].src must be an http(s) URL.");
            }
            $html = '<script' . (!empty($entry['async']) ? ' async' : '') . (!empty($entry['defer']) ? ' defer' : '')
                . ' src="' . h($src) . '"></script>' . "\n";
        }

        return ['index' => $index, 'order' => (int)($entry['order'] ?? 0), 'position' => $position, 'html' => $html];
    }

    /**
     * Join the HTML of a list of injections.
     *
     * @param list<string> $parts
     */
    private static function render(array $parts): string
    {
        return implode('', $parts);
    }
}
