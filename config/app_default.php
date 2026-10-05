<?php
declare(strict_types=1);

/**
 * TheMusicDev/Analytics ship-with defaults. Merged UNDER host values by the plugin's config/bootstrap.php, so hosts
 * win. With these defaults the plugin renders nothing: no host is allowed and no provider has its IDs.
 */
return [
    'Analytics' => [
        // Only requests for these hosts (no port) render anything. Staging and localhost stay out.
        'hosts' => [],
        // The known tracking providers, each reading its IDs from the environment and on only when they are all set and
        // well-formed. A host sets one provider's block to override it, or to `false` to turn it off for good; the
        // other providers keep their defaults.
        'tracking' => [
            'google' => ['measurementId' => env('GA_MEASUREMENT_ID')],
            'umami' => ['websiteId' => env('UMAMI_WEBSITE_ID'), 'src' => env('UMAMI_SRC')],
        ],
        // Scripts to put on every page: ['position' => 'head'|'body-end', 'order' => int, 'src' => url or 'html' => code].
        'inject' => [],
    ],
];
