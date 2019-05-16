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
    PROMETHEUS_PLUGIN_PATH . 'src/',
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

function prometheus_needs_basic_auth(): bool
{
    return isset($_ENV['PROMETHEUS_PASSWORD']);
}

function prometheus_verify_credentials(): bool
{
    $user = 'prometheus';
    if (!empty($_ENV['PROMETHEUS_USER'])) {
        $user = $_ENV['PROMETHEUS_USER'];
    }

    if (!isset($_ENV['PROMETHEUS_PASSWORD'])) {
        return false;
    }

    return hash_equals($user, $_SERVER['PHP_AUTH_USER']) &&
        hash_equals($_ENV['PROMETHEUS_PASSWORD'], $_SERVER['PHP_AUTH_PW']);
}

$plugins->add_hook('misc_start', 'prometheus_metrics');
function prometheus_metrics(): void
{
    global $mybb, $cache, $plugins;

    if ($mybb->get_input('action', MyBB::INPUT_STRING) !== 'prometheus_metrics') {
        return;
    }

    if (prometheus_needs_basic_auth() &&
        (!isset($_SERVER['PHP_AUTH_USER']) || !prometheus_verify_credentials())) {
        header('WWW-Authenticate: Basic realm="Prometheus Metrics"');
        header('HTTP/1.0 401 Unauthorized');

        exit();
    }

    http_response_code(200);
    header("Content-Type: text/plain; version=0.0.4");

    $registry = prometheus_get_default_metric_registry($cache);

    $plugins->run_hooks('prometheus_metrics_start', $registry);

    $metrics = $registry->render();

    $plugins->run_hooks('prometheus_metrics_end');

    echo $metrics;

    exit();
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