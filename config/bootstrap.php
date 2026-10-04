<?php
declare(strict_types=1);

use Cake\Core\Configure;

/**
 * Plugin config bootstrap (auto-required by BasePlugin::bootstrap()).
 *
 * Hosts define their own 'Analytics' block in config/app.php; write the defaults under any keys the host has not
 * set and never overwrite host values (shallow per-key `+` merge, the TheMusicDev plugin-config convention).
 */
$existing = (array)Configure::read('Analytics', []);
$defaults = (require __DIR__ . '/app_default.php')['Analytics'];
Configure::write('Analytics', $existing + $defaults);
