<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use TheMusicDev\Analytics\Lib\TagRenderer;

/**
 * Prints the tracking tags and injected scripts in the layout:
 *
 *     <head> … <?= $this->Analytics->head() ?> </head>
 *     … <?= $this->Analytics->bodyEnd() ?> </body>
 *
 * Both return an empty string when the request host is not in `Analytics.hosts`. The output is the same for every
 * visitor (no cookie or per-visitor logic), so pages stay cacheable.
 *
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class AnalyticsHelper extends Helper
{
    /**
     * Tracking tags and the `head` injections (injections come first).
     */
    public function head(): string
    {
        return TagRenderer::head($this->config(), $this->host());
    }

    /**
     * The `body-end` injections.
     */
    public function bodyEnd(): string
    {
        return TagRenderer::bodyEnd($this->config(), $this->host());
    }

    /**
     * The `Analytics` config block.
     *
     * @return array<array-key, mixed>
     */
    private function config(): array
    {
        return (array)Configure::read('Analytics', []);
    }

    /**
     * The request host, without a port.
     */
    private function host(): string
    {
        return $this->getView()->getRequest()->getUri()->getHost();
    }
}
