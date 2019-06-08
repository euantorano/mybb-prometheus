<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

use datacache;
use DB_Base;
use MyBB;
use MybbStuff\Prometheus\IMetricReporter;
use MybbStuff\Prometheus\Metric;

class OnlineUsersMetricReporter implements IMetricReporter
{
	/**
	 * @var \MyBB
	 */
	protected $mybb;

	/**
	 * @var \DB_Base
	 */
	protected $db;

	/**
	 * @var \datacache
	 */
	protected $cache;

	public function __construct(MyBB $mybb, DB_Base $db, datacache $cache)
	{
		$this->mybb = $mybb;
		$this->db = $db;
		$this->cache = $cache;
	}

	/**
	 * Get the name of the metric reporter.
	 *
	 * @return string
	 */
	function getName(): string
	{
		return 'online_users';
	}

	/**
	 * Get all of the metrics for this reporter.
	 *
	 * @return Metric[]
	 */
	function getMetrics(): array
	{
		$metrics = [];

		$timeSearch = TIME_NOW - ((int) $this->mybb->settings['wolcutoffmins'] * 60);

		$spiders = $this->cache->read('spiders');

		$numOnlineUsers = $numOnlineBots = $numOnlineGuests = 0;

		$prefix = TABLE_PREFIX;
		$sql = <<<SQL
SELECT DISTINCT s.sid, s.uid
FROM {$prefix}sessions s
WHERE s.time > {$timeSearch};
SQL;

		$query = $this->db->query($sql);

		while ($user = $this->db->fetch_array($query)) {
			$botKey = my_strtolower(str_replace("bot=", '', $user['sid']));

			if ((int) $user['uid'] > 0) {
				$numOnlineUsers++;
			} else if (my_strpos($user['sid'], "bot=") !== false && isset($spiders[$botKey])) {
				$numOnlineBots++;
			} else {
				$numOnlineGuests++;
			}
		}

		$metrics['mybb_online_users_total'] = (new Metric('mybb_online_users_total', Metric::TYPE_GAUGE))
			->setHelp('The total number of currently online users.')
			->setValue($numOnlineUsers + $numOnlineBots + $numOnlineGuests);

		$metrics['mybb_online_users_registered'] = (new Metric('mybb_online_users_registered', Metric::TYPE_GAUGE))
			->setHelp('The number of currently online users that are registered users.')
			->setValue($numOnlineUsers);

		$metrics['mybb_online_users_bots'] = (new Metric('mybb_online_users_bots', Metric::TYPE_GAUGE))
			->setHelp('The number of currently online users that are bots.')
			->setValue($numOnlineBots);

		$metrics['mybb_online_users_guests'] = (new Metric('mybb_online_users_guests', Metric::TYPE_GAUGE))
			->setHelp('The number of currently online users that are guests.')
			->setValue($numOnlineGuests);

		return $metrics;
	}
}