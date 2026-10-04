<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Provider;

/**
 * A tracking provider: turns its configured IDs into its tag. Values usually come from the environment, so a
 * missing or malformed ID is not an error: the provider is simply off and renders nothing (a typo in a server's
 * .env must not break every page).
 */
interface ProviderInterface
{
    /**
     * Whether the configured IDs are present and well-formed.
     *
     * @param array<array-key, mixed> $config The provider's block of `Analytics.tracking`.
     */
    public function isEnabled(array $config): bool;

    /**
     * The HTML for the page `<head>`. Only called when `isEnabled()` is true.
     *
     * @param array<array-key, mixed> $config The provider's block of `Analytics.tracking`.
     */
    public function tag(array $config): string;
}
