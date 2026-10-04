# TheMusicDev/Analytics

Tracking tags (Google Analytics 4, Umami) and any other scripts a site needs, for CakePHP 5 sites: rendered on
**production hosts only**, configured in one place, consent script first. Consent itself is the site's responsibility
(the plugin injects a consent tool such as Google's if asked, but enforces nothing).

> **Status: skeleton only, not released.** The plugin loads and merges its config; the helper, the Google and Umami
> providers and the injection are not built yet. The decisions and the delivery plan (A1–A4) are in
> [`docs/analytics-plugin-design.md`](docs/analytics-plugin-design.md). Nothing is on Packagist yet.

## Install (once released)

```bash
composer require themusicdev/analytics
bin/cake plugin load TheMusicDev/Analytics
```

## Configure (host `config/app.php`)

```php
'Analytics' => [
    'hosts' => ['example.com'],                       // only these hosts render anything
    'tracking' => [
        'google' => ['measurementId' => env('GA_MEASUREMENT_ID')],
        'umami' => ['websiteId' => env('UMAMI_WEBSITE_ID'), 'src' => env('UMAMI_SRC')],
    ],
    'inject' => [                                       // any script, e.g. a consent tool, rendered before the tags
        ['position' => 'head', 'order' => 10, 'src' => 'https://…', 'async' => true],
    ],
],
```

Host values win over `config/app_default.php`. With the defaults the plugin renders nothing.

## Tests

```bash
composer install
composer check        # phpunit + phpcs (CakePHP standard) + phpstan (level 8)
```

The tests run against a tiny test application in `tests/test_app`. There is no database.

## License

MIT, see [LICENSE](LICENSE).
