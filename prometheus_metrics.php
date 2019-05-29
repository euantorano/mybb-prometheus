<?php

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'prometheus_metrics.php');

include __DIR__ . '/global.php';

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
