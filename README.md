# TheMusicDev/Analytics

Tracking tags (Google Analytics 4, Umami) and any other scripts a site needs, for CakePHP 5 sites: rendered on
**production hosts only**. A known provider turns on by itself when its environment variables are set. The plugin asks
visitors for nothing and enforces no consent rule (see "Cookies and consent").

> **Status: the helper, the injection and the Google and Umami providers are built (A1–A3); not released yet.** What
> is left is the real-world check in a first site (A4). Decisions and plan:
> [`docs/analytics-plugin-design.md`](docs/analytics-plugin-design.md). Not on Packagist yet.

## Install

```bash
composer require themusicdev/analytics
bin/cake plugin load TheMusicDev/Analytics
```

## Configure

**Known providers turn on from the environment.** Set the variables (in the server's `.env`) and the provider's tag is
printed; nothing else to configure except which hosts may render:

```php
// config/app.php
'Analytics' => [
    'hosts' => ['example.com'],      // only these hosts render anything; staging and localhost stay out
],
```

| Provider key | Environment variables | On when |
|---|---|---|
| `google` (Google Analytics 4) | `GA_MEASUREMENT_ID` | it is `G-` plus letters and digits |
| `umami` | `UMAMI_WEBSITE_ID`, `UMAMI_SRC` | the ID is a UUID and `UMAMI_SRC` is an https URL |

A provider with a missing or malformed value is silently off, so a typo in a server's `.env` cannot break pages. The
environment variables never open a host: with no entry in `hosts` nothing renders, however many are set.

**Override from `app.php`.** Set a provider's block to use other values, or to `false` to turn it off for good; the other
providers keep their defaults (`tracking` merges one level deep):

```php
'Analytics' => [
    'hosts' => ['example.com'],
    'tracking' => [
        'google' => ['measurementId' => 'G-XXXXXXX'],   // instead of GA_MEASUREMENT_ID
        'umami' => false,                               // off, even if UMAMI_* are set
    ],
    'inject' => [                                       // any script, rendered before the tags
        ['position' => 'head', 'order' => 10, 'src' => 'https://…', 'async' => true],
    ],
],
```

Host values win over `config/app_default.php`, where the defaults are `env()` calls. New providers are added to the plugin
as a class plus a line in that file.

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

**What each provider prints:** `google` is the Google Analytics 4 gtag.js snippet; `umami` is
`<script defer src=… data-website-id=…>`. The config keys are `measurementId` (google) and `websiteId` + `src` (umami).

**Injections** are any script a site needs (a tag manager, a snippet): each entry has `html` **or**
`src` (never both), an optional `position` (`head`, the default, renders **before** the tracking tags, or `body-end`),
an optional `order` (lower first; ties keep their config order) and, for `src`, optional `async` / `defer`. They are
code in your config, so a malformed entry or an unknown tracking provider key **throws**, on every host, so a typo is
caught in development.

## Cookies and consent

The plugin sends nothing on hosts you have not listed, but it **does not ask visitors for consent and enforces no
rule** about it. Google Analytics sets cookies and needs prior consent for visitors in the EU and UK (and notice or
opt-out in several US states); Umami, which uses no cookies, generally does not. Whether and how to ask is the
site's responsibility.

## Tests

```bash
composer install
composer check        # phpunit + phpcs (CakePHP standard) + phpstan (level 8)
```

The tests run against a tiny test application in `tests/test_app`. There is no database.

## License

MIT, see [LICENSE](LICENSE).
