<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use TestApp\Application;
use TheMusicDev\Analytics\View\Helper\AnalyticsHelper;

/**
 * The helper a layout calls: it reads the `Analytics` config and the request host.
 */
final class AnalyticsHelperTest extends TestCase
{
    private mixed $original;

    protected function setUp(): void
    {
        parent::setUp();
        $this->original = Configure::read('Analytics');
        $app = new Application(CONFIG);
        $app->bootstrap();
        $app->pluginBootstrap();
        Configure::write('Analytics', [
            'hosts' => ['example.test'],
            'tracking' => ['google' => ['measurementId' => 'G-L6M06JFS3F']],
            'inject' => [['position' => 'body-end', 'html' => '<i>end</i>']],
        ]);
    }

    protected function tearDown(): void
    {
        Configure::write('Analytics', $this->original);
        parent::tearDown();
    }

    private function helper(string $host): AnalyticsHelper
    {
        $view = new View(new ServerRequest(['environment' => ['HTTP_HOST' => $host]]));
        /** @var \TheMusicDev\Analytics\View\Helper\AnalyticsHelper $helper */
        $helper = $view->loadHelper('TheMusicDev/Analytics.Analytics');

        return $helper;
    }

    public function testHeadAndBodyEndRenderOnAnAllowedHost(): void
    {
        $helper = $this->helper('example.test');

        $this->assertStringContainsString('gtag/js?id=G-L6M06JFS3F', $helper->head());
        $this->assertSame("<i>end</i>\n", $helper->bodyEnd());
    }

    public function testTheHostIsTakenWithoutItsPort(): void
    {
        $this->assertStringContainsString('gtag', $this->helper('example.test:8765')->head());
    }

    public function testNothingRendersOnAnotherHost(): void
    {
        $helper = $this->helper('staging.example.test');

        $this->assertSame('', $helper->head());
        $this->assertSame('', $helper->bodyEnd());
    }
}
