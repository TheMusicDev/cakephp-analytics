# Analytics plugin: design of record

> **Status: A1–A3 built (2026-10-04), A4 pending; designed 2026-10-03, revised the same day.** Composer package `themusicdev/analytics`, GitHub repo
> `TheMusicDev/cakephp-analytics`, namespace `TheMusicDev\Analytics`, plugin name `TheMusicDev/Analytics`. This revision replaces the first design (a self-built consent banner,
> then a "consent provider" abstraction): the maintainer decided on 2026-10-03 that v1 has **no custom consent
> code and no consent logic**.

## 1. Goal

Put the tracking tags and any other third-party scripts a site needs onto its pages, **on production hosts only**,
configured in one place, in the right order. That is all.

## 2. Decisions

| # | Decision | Why |
|---|---|---|
| D1 | **Tracking providers in v1: `google` (Google Analytics 4) and `umami`.** A provider turns its IDs into its tag. Several can be on at once; a provider is on when its IDs are set. More providers can be added later as classes. | Those are the two in use. |
| D2 | **Injections: any code a site wants on its pages.** A named list in host config. Each entry is raw HTML (an inline script) or a script URL, with a `position` (`head`, which is rendered **before** the tracking tags, or `body-end`) and an optional `order`. | Google's consent tool is a script Google hosts and you paste into the site, so it is just an injection. Nothing about consent is special-cased. |
| D3 | **No consent providers and no consent logic in v1.** The plugin does not check whether a consent tool is configured, does not pair providers with consent, and does not hold back tags. | Any such rule assumes how Google or Umami behave today and breaks when they change. (2026-10-03) |
| D4 | **No custom consent code in v1.** A self-built banner is possible later, as another injection or a provider; not now. | Legal upkeep and proof-of-consent records are not ours to carry yet. |
| D5 | **Nothing renders unless the host is allowed:** only hosts in `Analytics.hosts` get any output. | Staging (`<client>.tmdapps.dev`) and local development must never send hits; same idea as `Seo.robots.allowHosts`. |
| D6 | **IDs come from the environment** (`env('GA_MEASUREMENT_ID')`, `env('UMAMI_WEBSITE_ID')`, `env('UMAMI_SRC')`) into the host config. | They differ per environment; the `.env` is the agreed place. |
| D7 | **Output is the same for every visitor.** No per-visitor or cookie-dependent HTML from the server. | These sites sit behind a CDN and opcache. |
| D8 | **Injections are host config only**, never read from a database or an admin screen. | They are raw HTML put on every page: they are code, so they belong in version control and review. |
| D9 | **Responsibility for consent sits with the site, not the plugin**, and the README says so plainly: Google Analytics needs prior consent for visitors in the EU/UK (and notice/opt-out in several US states); the consent script goes in an injection at `position => head`, which renders before the tracking tags. | Follows from D3. |

## 3. How it works

```
layout <head>:   <?= $this->Analytics->head() ?>
                   1. injections with position "head", sorted by order   (e.g. the consent script)
                   2. the tag of every enabled tracking provider          (google, umami)
layout </body>:  <?= $this->Analytics->bodyEnd() ?>
                   injections with position "body-end"
```

Both helper methods return an empty string when the request host is not in `Analytics.hosts` (D5).

```php
// config/app.php (host)
'Analytics' => [
    'hosts' => ['themusicdev.llc'],
    'tracking' => [
        'google' => ['measurementId' => env('GA_MEASUREMENT_ID')],
        'umami' => ['websiteId' => env('UMAMI_WEBSITE_ID'), 'src' => env('UMAMI_SRC')],
    ],
    'inject' => [
        // e.g. Google's consent tool: a script URL from the site's Google account
        ['position' => 'head', 'order' => 10, 'src' => 'https://…', 'async' => true],
        ['position' => 'head', 'order' => 20, 'html' => '<script>/* consent-mode defaults */</script>'],
    ],
],
```

## 4. Planned shape

- `src/View/Helper/AnalyticsHelper.php`: `head()`, `bodyEnd()`.
- `src/Provider/` : one small class per tracking provider (`GoogleProvider`, `UmamiProvider`), each with
  `isEnabled(array $config): bool` and `tag(array $config): string`. Output is escaped properly
  (IDs are validated against the expected format: `G-` + alphanumerics for Google, a UUID for Umami).
- `config/app_default.php` + `config/bootstrap.php`: the usual plugin-config convention (host wins).
- Tests (in the plugin): nothing renders off-production; each provider renders only when its IDs are set; a
  malformed ID renders nothing; injections come out in `order`, before the tracking tags for `head` and
  separately for `body-end`; entries with neither `html` nor `src` are ignored; a `src` is HTML-escaped.
  A manual check in a real browser per site before launch (network requests and cookies) is part of done.
- Docs: plugin README (install, config, the consent responsibility note, how to add Google's consent tool).

## 5. Delivery plan

| | Feature | Done when |
|---|---|---|
| A1 | Plugin skeleton, config, host gating, `head()` / `bodyEnd()` with injections | off-production renders nothing; injections render in order |
| A2 | `google` provider | the GA4 tag appears with the configured ID on a production host only |
| A3 | `umami` provider | the Umami script tag appears with its website ID and source |
| A4 | README, consent note, wire into the reference app's layout | the reference app shows the tags on the production host only |

## 6. To do outside the code (the maintainer)

- Create the Google Analytics 4 property for themusicdev.llc and note the measurement ID.
- Optional: an AdSense or Ad Manager account, to try Google's consent tool (it is set up there, under
  Privacy & messaging, "European regulations"). Not confirmed: whether it can be used by a site that runs only
  Google Analytics, with no ads.
- Provide the Umami website ID and script URL (already in use on the Astro site).
