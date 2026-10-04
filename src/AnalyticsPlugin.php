<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics;

use Cake\Core\BasePlugin;

/**
 * TheMusicDev/Analytics: tracking tags (Google Analytics 4, Umami) and injected scripts, rendered on production
 * hosts only. The design of record is docs/analytics-plugin-design.md; configuration lives in config/app_default.php
 * and config/bootstrap.php, merged UNDER the host's `Analytics.*` values.
 */
final class AnalyticsPlugin extends BasePlugin
{
}
