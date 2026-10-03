# Analytics plugin: design of record

> **Status: designed 2026-10-03, not built. D2 is under review** (research on the IAB fee and the legal
> concerns of a home-made banner: `TheMusicDev/cakephp-conventions`, `docs/analytics-discussion.md`; the
> recommendation there is a provider seam, see D12). Decisions below are agreed; the three questions at the end
> are the only things left to settle before building. Package name when built:
> `themusicdev/cakephp-analytics`, namespace `TheMusicDev\Analytics`.

## 1. Goal

Every TheMusicDev site that uses Google Analytics gets the same two things from one plugin, instead of a
pasted snippet:

1. the **GA4 tag**, on production hosts only;
2. a **consent banner** that makes sure **no analytics cookie is set, and no data is sent to Google, until the
   visitor accepts**, and that lets them change their mind later.

## 2. Decisions

| # | Decision | Why |
|---|---|---|
| D1 | **Ask every visitor**, on every site; nothing is sent until they accept. | EU/UK law requires prior consent for analytics cookies; several US states require notice and opt-out; a site can have visitors from anywhere. One behaviour, no guessing where a visitor is. Cost: visitors who ignore or decline are not counted. (2026-10-03) |
| D2 *(under review)* | **Build our own small banner** inside the plugin as the free default; no paid vendor. | The page only has to tell Google "denied" at load and "granted" on accept. A library we do not maintain is a risk we do not need, and the one candidate looked unmaintained. (2026-10-03) |
| D3 | **"Basic" Consent Mode v2:** `gtag.js` is not even loaded until the visitor accepts. | The "advanced" mode loads the script immediately and sends cookieless pings to Google while consent is denied; some regulators object to that. Basic mode sends nothing. Cost: no modelled conversions for non-consenting visitors, which we do not want anyway. |
| D4 | **Client-side only.** The server never reads the consent cookie to decide what HTML to render. | These sites sit behind a CDN and opcache: pages must be identical for everyone to be cacheable. |
| D5 | **GA4 only in v1.** The tag is produced by one helper method so another provider can be added later as another class. | No abstraction for a second provider nobody has asked for. The plugin is named `Analytics`, not `GoogleAnalytics`, so renaming is not needed later. |
| D6 | **One consent category** ("analytics") now; categories are config, so adding "marketing" later is a config change plus banner text. | Only GA is planned. |
| D7 | **A banner nobody answers means "denied".** It stays visible as a bar at the bottom of the page, not a wall that blocks the content. | Ignoring is not consent; a blocking overlay is hostile and unnecessary. |
| D8 | **The banner script is inline in the element, not a plugin asset.** | A plugin's `webroot` is served through a symlink (`bin/cake plugin assets symlink`), which is fragile on shared hosting; an inline script has no path to get wrong. |
| D9 | **Nothing renders unless configured:** no measurement ID, or a host not in `Analytics.hosts`, produces no tag and no banner. | Staging (`<client>.tmdapps.dev`) and local development must never send hits or show a banner; same idea as `Seo.robots.allowHosts`. |
| D10 | **The measurement ID is not hard-coded:** the host reads it from the environment (`env('GA_MEASUREMENT_ID')`) into `Analytics.ga.measurementId`. | It is not secret, but it differs per environment, and the `.env` is the agreed place for per-environment values. |
| D12 *(proposed)* | **The banner is a pluggable "consent provider".** `builtin` (our minimal banner, the default) or `external` (a third-party platform's script snippet, chosen and paid for by the client, for sites that need a Google-certified platform, e.g. serving Google ads in the EEA/UK/CH). The plugin still owns host gating, tag order, the Consent Mode defaults and the GA tag. | The IAB Europe fee (€1,575/yr) applies to being a certified platform, not to using Google Analytics; a client with Google ads needs a certified vendor. One plugin serves both kinds of site. We build `builtin` first and the `external` hook when a client needs it. |
| D13 *(proposed)* | **A small consent record** for the built-in provider: a POST of (choice, banner version, timestamp) to the site, stored in a table with a random pseudonymous id kept in the consent cookie. | GDPR expects the site to be able to *demonstrate* consent; a cookie on the visitor's device does not. Needs a decision, see Q-D. |
| D11 | **The plugin provides a "cookie settings" link** that reopens the banner. The **cookie policy page is the site's own content** (the README includes starter wording listing the cookies). | A visitor must be able to withdraw as easily as they gave consent. A policy page is legal copy specific to each client. |

## 3. How it works

```
page load
  inline: window.dataLayer, gtag() defined
  inline: gtag('consent','default', {analytics_storage:'denied', ad_storage:'denied',
                                     ad_user_data:'denied', ad_personalization:'denied'})
  read cookie `tmd_consent`
    "granted"  → loadGa()                (load gtag.js, gtag('js'), gtag('config', ID),
                                          gtag('consent','update',{analytics_storage:'granted'}))
    "denied"   → do nothing
    absent     → show the banner

banner: [Accept] [Reject]   (equal prominence)
  Accept → set cookie "granted", loadGa(), hide banner
  Reject → set cookie "denied", hide banner

"cookie settings" link → clear the cookie, show the banner again
                         (if previously granted: tell GA 'denied' and stop sending)
```

The plugin's own cookie (`tmd_consent`) is a first-party, strictly-necessary cookie that only records the
choice (value, a version number, a timestamp). Its name and lifetime are config.

## 4. Planned shape

- `src/View/Helper/AnalyticsHelper.php`: `tag()` (the consent-default block plus the loader, for the
  `<head>`), `banner()` (the element, before `</body>`), `settingsLink()` (a link or button for the footer).
  Each returns an empty string when the plugin is not active for this host (D9).
- `templates/element/consent_banner.php`: daisyUI markup (`role="dialog"`, `aria-labelledby`, not modal,
  keyboard reachable) and the inline script. Overridable at
  `templates/plugin/TheMusicDev/Analytics/element/consent_banner.php`.
- `config/app_default.php` + `config/bootstrap.php`: the usual plugin-config convention (host wins).
- Config under `Analytics`:

```php
'Analytics' => [
    'hosts' => ['example.com'],            // only these hosts render anything (D9)
    'ga' => ['measurementId' => env('GA_MEASUREMENT_ID')],
    'consent' => [
        'cookie' => 'tmd_consent',
        'days' => 365,                      // see question Q-A
        'text' => '…',                     // banner copy; see question Q-C
        'policyUrl' => null,                // a router-built URL the host passes in; never typed
    ],
],
```

- Tests (in the plugin): the tag and banner are empty off-production or without an ID; the consent default
  is emitted *before* anything that could load `gtag.js`; `gtag.js` is not present in the HTML (it is loaded by
  script after consent); the banner has both buttons; overriding the element works. **A manual check in a real
  browser per site before launch is part of the definition of done** (cookies and network requests before and
  after Accept), because a silent consent bug is a legal problem, not a cosmetic one.
- Docs: plugin README (install, configure, the footer link, starter cookie-policy wording), `docs/decisions.md`
  for build-time notes.

## 5. Delivery plan (small)

| | Feature | Done when |
|---|---|---|
| A1 | Plugin skeleton, config, `hosts` and ID gating, `tag()` emitting the consent default | off-production and no-ID render nothing; production renders the default block |
| A2 | Banner element, cookie, load-on-accept, reject | in a real browser: nothing sent before Accept; Accept loads GA; Reject never does; choice survives reload |
| A3 | `settingsLink()` and withdraw | link reopens the banner; switching to denied stops hits |
| A4 | README, cookie-policy wording, wire into the reference app's layout | the reference app shows the banner on the production host only |

## 6. Questions left (small; defaults proposed)

**Q-D. Keep a consent record (D13)?** It closes the "demonstrate consent" gap but adds a table, an endpoint and a
retention rule. I propose **yes** for the built-in provider, storing no IP address and no personal data.

**Q-A. How long should the choice be remembered?** I propose **365 days**, then ask again. (Some regulators
suggest refreshing consent roughly once a year; the cookie lifetime is configurable per site.)

**Q-B. Banner position and style?** I propose a **bottom bar, non-blocking**, daisyUI `card`/`alert` styling
that follows the site's theme, with "Accept" and "Reject" the same size.

**Q-C. Default wording?** I propose: *"We use cookies to measure how visitors use this site (Google
Analytics). Nothing is sent until you accept. [Accept] [Reject]"*, with a link to the site's cookie policy
when `policyUrl` is set. Each site can override the text.
