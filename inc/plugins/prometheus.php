<?php

use MybbStuff\Core\ClassLoader;
use MybbStuff\Prometheus\MetricReporterRegistry;
use MybbStuff\Prometheus\MetricReporters\{AwaitingActivationMetricReporter,
	BoardStatsMetricReporter,
	MailQueueMetricReporter,
	MostOnlineMetricReporter,
	ReportedContentMetricReporter,
	VersionCodeMetricReporter};

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

defined('MYBBSTUFF_CORE_PATH') || define('MYBBSTUFF_CORE_PATH', __DIR__ . '/MybbStuff/Core');
defined('PROMETHEUS_PLUGIN_PATH') || define('PROMETHEUS_PLUGIN_PATH', __DIR__ . '/MybbStuff/Prometheus');

defined('MYBBSTUFF_PLUGINS_CACHE_NAME') || define('MYBBSTUFF_PLUGINS_CACHE_NAME', 'mybbstuff_plugins');

require_once MYBBSTUFF_CORE_PATH . '/src/ClassLoader.php';

$classLoader = ClassLoader::getInstance();
$classLoader->registerNamespace(
    'MybbStuff\\Prometheus\\',
	PROMETHEUS_PLUGIN_PATH . '/src/',
);
$classLoader->register();

function prometheus_info(): array
{
    return [
        'name'          => 'Prometheus',
        'description'   => 'A MyBB plugin to expose metrics to Prometheus.',
        'website'       => 'https://www.mybbstuff.com',
        'author'        => 'Euan Torano',
        'authorsite'    => '',
        'version'       => '0.0.1',
        'compatibility' => '18*',
        'codename'      => 'mybbstuff_prometheus',
    ];
}

function prometheus_cache_read(datacache $cache, string $name): array
{
    $cached = $cache->read($name);

    if ($cached === false) {
        $cached = [];
    }

    return $cached;
}

function prometheus_install(): void
{
    global $cache;

    $pluginsCache = prometheus_cache_read($cache, MYBBSTUFF_PLUGINS_CACHE_NAME);
    if (isset($pluginsCache['prometheus'])) {
        unset($pluginsCache['prometheus']);
    }
    $cache->update(MYBBSTUFF_PLUGINS_CACHE_NAME, $pluginsCache);
}

function prometheus_is_installed(): bool
{
    global $cache;

    $pluginsCache = prometheus_cache_read($cache, MYBBSTUFF_PLUGINS_CACHE_NAME);
    return isset($pluginsCache['prometheus']);
}

function prometheus_uninstall(): void
{
    global $cache;

    $pluginsCache = prometheus_cache_read($cache, MYBBSTUFF_PLUGINS_CACHE_NAME);
    if (isset($pluginsCache['prometheus'])) {
        unset($pluginsCache['prometheus']);
    }
    $cache->update(MYBBSTUFF_PLUGINS_CACHE_NAME, $pluginsCache);
}

function prometheus_activate(): void
{
    global $cache;

    $pluginInfo = prometheus_info();
    $pluginsCache = prometheus_cache_read($cache, MYBBSTUFF_PLUGINS_CACHE_NAME);
    $pluginsCache['prometheus'] = [
        'version' => $pluginInfo['version'],
    ];
    $cache->update(MYBBSTUFF_PLUGINS_CACHE_NAME, $pluginsCache);
}

function prometheus_deactivate(): void
{

}

function prometheus_get_default_metric_registry(datacache $cache): MetricReporterRegistry
{
    $registry = MetricReporterRegistry::getInstance();

    $registry->addMetricReporter(new AwaitingActivationMetricReporter($cache));
    $registry->addMetricReporter(new BoardStatsMetricReporter($cache));
    $registry->addMetricReporter(new MailQueueMetricReporter($cache));
    $registry->addMetricReporter(new MostOnlineMetricReporter($cache));
    $registry->addMetricReporter(new ReportedContentMetricReporter($cache));
    $registry->addMetricReporter(new VersionCodeMetricReporter($cache));

    return $registry;
}