<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Provider;

/**
 * Umami (cookie-less analytics). Config: `websiteId` (a UUID) and `src` (the https URL of the Umami script,
 * for example `https://cloud.umami.is/script.js` or your own server's `/script.js`). Env vars: `UMAMI_WEBSITE_ID`,
 * `UMAMI_SRC`.
 */
final class UmamiProvider implements ProviderInterface
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * @inheritDoc
     */
    public function isEnabled(array $config): bool
    {
        return preg_match(self::UUID_PATTERN, $this->value($config, 'websiteId')) === 1
            && $this->httpsUrl($this->value($config, 'src'));
    }

    /**
     * @inheritDoc
     */
    public function tag(array $config): string
    {
        return '<script defer src="' . h($this->value($config, 'src')) . '" data-website-id="'
            . h($this->value($config, 'websiteId')) . '"></script>' . "\n";
    }

    /**
     * A string config value, or '' when absent or not a string.
     *
     * @param array<array-key, mixed> $config
     */
    private function value(array $config, string $key): string
    {
        $value = $config[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * Whether the value is a well-formed https URL.
     */
    private function httpsUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false && str_starts_with($url, 'https://');
    }
}
