<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Test\TestCase;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TestApp\Application;

/**
 * The skeleton: the plugin loads in an application and its config defaults are merged under the host's values.
 */
final class AnalyticsPluginTest extends TestCase
{
    private mixed $original;

    protected function setUp(): void
    {
        parent::setUp();
        $this->original = Configure::read('Analytics');
        Configure::delete('Analytics');
    }

    protected function tearDown(): void
    {
        Configure::write('Analytics', $this->original);
        parent::tearDown();
    }

    private function boot(): void
    {
        $app = new Application(CONFIG);
        $app->bootstrap();
        $app->pluginBootstrap();
    }

    public function testThePluginLoadsInAnApplication(): void
    {
        $this->boot();

        $this->assertTrue(Plugin::isLoaded('TheMusicDev/Analytics'));
    }

    public function testWithoutHostConfigNothingIsAllowedAndNothingIsEnabled(): void
    {
        $this->boot();

        $this->assertSame([], Configure::read('Analytics.hosts'));
        $this->assertSame([], Configure::read('Analytics.tracking'));
        $this->assertSame([], Configure::read('Analytics.inject'));
    }

    public function testHostValuesWinOverTheDefaults(): void
    {
        Configure::write('Analytics', ['hosts' => ['example.test']]);

        $this->boot();

        $this->assertSame(['example.test'], Configure::read('Analytics.hosts'), 'the host value is kept');
        $this->assertSame([], Configure::read('Analytics.tracking'), 'a key the host did not set gets the default');
    }
}
