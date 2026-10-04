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
        // Tracking providers, each on when its IDs are set (e.g. 'google' => ['measurementId' => '…']).
        'tracking' => [],
        // Scripts to put on every page: ['position' => 'head'|'body-end', 'order' => int, 'src' => url or 'html' => code].
        'inject' => [],
    ],
];
