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

defined('PLUGINLIBRARY') || define('PLUGINLIBRARY', __DIR__ . '/pluginlibrary.php');

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

    $pluginInfo = prometheus_info();
    $pluginsCache = prometheus_cache_read($cache, MYBBSTUFF_PLUGINS_CACHE_NAME);
    $pluginsCache['prometheus'] = [
        'version' => $pluginInfo['version'],
    ];
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
    global $cache, $PL, $lang;

    $lang->load('prometheus');

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->prometheus_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    $pluginsCache = prometheus_cache_read($cache, MYBBSTUFF_PLUGINS_CACHE_NAME);
    if (isset($pluginsCache['prometheus'])) {
        unset($pluginsCache['prometheus']);
    }
    $cache->update(MYBBSTUFF_PLUGINS_CACHE_NAME, $pluginsCache);

    $PL->settings_delete('prometheus', true);
}

function prometheus_activate(): void
{
    global $PL, $lang;

    $lang->load('prometheus');

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message($lang->prometheus_pluginlibrary_missing, 'error');
        admin_redirect('index.php?module=config-plugins');
    }

    $PL or require_once PLUGINLIBRARY;

    $PL->settings(
        'prometheus',
        $lang->setting_group_prometheus,
        $lang->setting_group_prometheus_desc,
        [
            'auth_username' => [
                'title' => $lang->setting_prometheus_auth_username,
                'description' => $lang->setting_prometheus_auth_username_desc,
                'value' => 'prometheus',
                'optionscode' => 'text',
            ],
            'auth_password' => [
                'title' => $lang->setting_prometheus_auth_password,
                'description' => $lang->setting_prometheus_auth_password_desc,
                'value' => 'password',
                'optionscode' => 'text',
            ],
        ]
    );
}

function prometheus_deactivate(): void
{

}

function prometheus_verify_credentials(MyBB $mybb, ?string $user, ?string $password): bool
{
    return hash_equals($mybb->settings['prometheus_username'], $user) &&
        hash_equals($mybb->settings['prometheus_password'], $password);
}

$plugins->add_hook('misc_start', 'prometheus_metrics');
function prometheus_metrics(): void
{
    global $mybb, $cache, $plugins;

    if ($mybb->get_input('action', MyBB::INPUT_STRING) !== 'prometheus_metrics') {
        return;
    }

    if (!isset($_SERVER['PHP_AUTH_USER']) ||
        !prometheus_verify_credentials($mybb, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
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