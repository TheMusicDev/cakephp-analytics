# TheMusicDev/Analytics

Tracking tags (Google Analytics 4, Umami) and any other scripts a site needs, for CakePHP 5 sites: rendered on
**production hosts only**, configured in one place, consent script first. Consent itself is the site's responsibility
(the plugin injects a consent tool such as Google's if asked, but enforces nothing).

> **Status: the helper, the injection and the Google and Umami providers are built (A1–A3); not released yet.** What
> is left is the real-world check in a first site (A4). Decisions and plan:
> [`docs/analytics-plugin-design.md`](docs/analytics-plugin-design.md). Not on Packagist yet.

## Install

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

## Use

Print the tags in your layout:

```php
<head>
    …
    <?= $this->Analytics->head() ?>
</head>
<body>
    …
    <?= $this->Analytics->bodyEnd() ?>
</body>
```

Load the helper once in `AppView::initialize()`: `$this->addHelper('TheMusicDev/Analytics.Analytics');`.
Both methods return an empty string unless the request host is in `Analytics.hosts`, and the output is the same for
every visitor, so pages stay cacheable.

**Tracking providers** (each is on when its IDs are present and well-formed; otherwise it is silently off, so a typo
in a server's `.env` cannot break pages):

| Key | Config | Output |
|---|---|---|
| `google` | `measurementId`: `G-` plus letters and digits | the Google Analytics 4 gtag.js snippet |
| `umami` | `websiteId` (UUID), `src` (https URL of the script) | `<script defer src=… data-website-id=…>` |

**Injections** are any script a site needs (a consent tool, a tag manager, a snippet): each entry has `html` **or**
`src` (never both), an optional `position` (`head`, the default, renders **before** the tracking tags, or `body-end`),
an optional `order` (lower first; ties keep their config order) and, for `src`, optional `async` / `defer`. They are
code in your config, so a malformed entry or an unknown tracking provider key **throws**, on every host, so a typo is
caught in development.

## Consent is your responsibility

The plugin sends nothing on hosts you have not listed, but it **does not ask visitors for consent and enforces no
rule** about it. Google Analytics sets cookies and needs prior consent for visitors in the EU and UK (and notice or
opt-out in several US states); Umami, which uses no cookies, generally does not. If the site needs consent, add a
consent tool as an injection at `position => head` so it renders first, for example Google's own consent tool
(set up in an AdSense or Ad Manager account under Privacy & messaging):

```php
'inject' => [
    ['position' => 'head', 'order' => 10, 'src' => 'https://fundingchoicesmessages.google.com/i/pub-…?ers=1', 'async' => true],
    ['position' => 'head', 'order' => 20, 'html' => "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('consent','default',{analytics_storage:'denied',ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied'});</script>"],
],
```

Check the consent tool's own instructions for the exact script and for how it updates Consent Mode.

## Tests

```bash
composer install
composer check        # phpunit + phpcs (CakePHP standard) + phpstan (level 8)
```

The tests run against a tiny test application in `tests/test_app`. There is no database.

## License

MIT, see [LICENSE](LICENSE).
