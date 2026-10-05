<?php
declare(strict_types=1);

namespace TheMusicDev\Analytics\Test\TestCase;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TestApp\Application;
use TheMusicDev\Analytics\Lib\TagRenderer;

/**
 * The skeleton: the plugin loads in an application and its config defaults are merged under the host's values.
 */
final class AnalyticsPluginTest extends TestCase
{
    private mixed $original;

    private const ENV = ['GA_MEASUREMENT_ID', 'UMAMI_WEBSITE_ID', 'UMAMI_SRC'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->original = Configure::read('Analytics');
        Configure::delete('Analytics');
        $this->clearEnv();
    }

    protected function tearDown(): void
    {
        $this->clearEnv();
        Configure::write('Analytics', $this->original);
        parent::tearDown();
    }

    private function clearEnv(): void
    {
        foreach (self::ENV as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    private function setAllEnv(): void
    {
        putenv('GA_MEASUREMENT_ID=G-ENVTEST123');
        putenv('UMAMI_WEBSITE_ID=11111111-2222-3333-4444-555555555555');
        putenv('UMAMI_SRC=https://umami.example.test/script.js');
    }

    private function head(): string
    {
        return TagRenderer::head((array)Configure::read('Analytics'), 'example.test');
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
        $this->assertSame(['google', 'umami'], array_keys((array)Configure::read('Analytics.tracking')), 'the known providers');
        $this->assertSame([], Configure::read('Analytics.inject'));
    }

    public function testHostValuesWinOverTheDefaults(): void
    {
        Configure::write('Analytics', ['hosts' => ['example.test']]);

        $this->boot();

        $this->assertSame(['example.test'], Configure::read('Analytics.hosts'), 'the host value is kept');
        $this->assertSame([], Configure::read('Analytics.inject'), 'a key the host did not set gets the default');
    }

    public function testWithNoEnvVarsAKnownProviderIsOffEvenOnAnAllowedHost(): void
    {
        Configure::write('Analytics', ['hosts' => ['example.test']]);

        $this->boot();

        $this->assertSame('', $this->head());
    }

    public function testTheKnownProvidersTurnOnFromTheirEnvVarsAlone(): void
    {
        $this->setAllEnv();
        Configure::write('Analytics', ['hosts' => ['example.test']]);

        $this->boot();

        $head = $this->head();
        $this->assertStringContainsString("gtag('config','G-ENVTEST123')", $head);
        $this->assertStringContainsString(
            'src="https://umami.example.test/script.js" data-website-id="11111111-2222-3333-4444-555555555555"',
            $head,
        );
    }

    public function testEachProviderNeedsAllOfItsEnvVars(): void
    {
        putenv('UMAMI_WEBSITE_ID=11111111-2222-3333-4444-555555555555');
        Configure::write('Analytics', ['hosts' => ['example.test']]);

        $this->boot();

        $this->assertSame('', $this->head(), 'Umami without UMAMI_SRC is off, and Google has no ID');
    }

    public function testTheEnvVarsDoNotOpenAnyHost(): void
    {
        $this->setAllEnv();
        Configure::write('Analytics', ['hosts' => ['example.test']]);

        $this->boot();

        $this->assertSame('', TagRenderer::head((array)Configure::read('Analytics'), 'stage.example.test'));
        $this->assertSame('', TagRenderer::head((array)Configure::read('Analytics'), 'localhost'));
    }

    public function testAHostOverridesOneProviderAndKeepsTheOtherDefault(): void
    {
        $this->setAllEnv();
        Configure::write('Analytics', [
            'hosts' => ['example.test'],
            'tracking' => ['google' => ['measurementId' => 'G-HOSTSET999']],
        ]);

        $this->boot();

        $head = $this->head();
        $this->assertStringContainsString("gtag('config','G-HOSTSET999')", $head, 'the host value wins');
        $this->assertStringNotContainsString('G-ENVTEST123', $head);
        $this->assertStringContainsString('umami.example.test', $head, 'Umami keeps its env default');
    }

    public function testAHostTurnsAProviderOffWithFalse(): void
    {
        $this->setAllEnv();
        Configure::write('Analytics', ['hosts' => ['example.test'], 'tracking' => ['google' => false]]);

        $this->boot();

        $head = $this->head();
        $this->assertStringNotContainsString('gtag', $head, 'Google is off although its env var is set');
        $this->assertStringContainsString('umami.example.test', $head);
    }
}
