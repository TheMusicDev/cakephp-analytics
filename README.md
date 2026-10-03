# TheMusicDev Analytics (designed, not built)

Tracking tags (Google Analytics 4, Umami) and any other scripts a site needs, for TheMusicDev CakePHP sites:
rendered on production hosts only, configured in one place, consent script first. Consent itself is the site's
responsibility (the plugin injects Google's consent tool if asked, but enforces nothing).

**Status:** design only. See [docs/analytics-plugin-design.md](docs/analytics-plugin-design.md) for the decisions,
the planned shape and the delivery plan. The org-level discussion that led to it is in
`TheMusicDev/cakephp-conventions`, `docs/analytics-discussion.md`.
