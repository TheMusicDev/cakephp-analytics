<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Provider;

/**
 * Google Analytics 4 (gtag.js). Config: `measurementId` (`G-` plus letters and digits). Consent is not handled
 * here: the site injects a consent tool ahead of this tag when it needs one (see the README).
 */
final class GoogleProvider implements ProviderInterface
{
    private const ID_PATTERN = '/^G-[A-Z0-9]{4,20}$/';

    /**
     * @inheritDoc
     */
    public function isEnabled(array $config): bool
    {
        return preg_match(self::ID_PATTERN, $this->id($config)) === 1;
    }

    /**
     * @inheritDoc
     */
    public function tag(array $config): string
    {
        // The ID matched ID_PATTERN, so it is safe to embed in a URL and a JavaScript string.
        $id = $this->id($config);

        return '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $id . '"></script>' . "\n"
            . '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
            . "gtag('js',new Date());gtag('config','" . $id . "');</script>\n";
    }

    /**
     * The configured measurement ID, or '' when absent.
     *
     * @param array<array-key, mixed> $config
     */
    private function id(array $config): string
    {
        $id = $config['measurementId'] ?? '';

        return is_string($id) ? $id : '';
    }
}
