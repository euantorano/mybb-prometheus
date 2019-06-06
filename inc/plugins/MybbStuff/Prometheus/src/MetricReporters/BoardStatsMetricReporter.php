<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

use datacache;
use DB_Base;
use MyBB;
use MybbStuff\Prometheus\Metric;

class BoardStatsMetricReporter extends CacheBasedMetricReporter
{
    private static $gaugeStats = [
        'numthreads' => [
            'name' => 'mybb_stats_num_threads',
	        'help' => 'The number of threads in all forums.',
        ],
        'numunapprovedthreads' => [
            'name' => 'mybb_stats_num_unapproved_threads',
	        'help' => 'The number of unapproved threads in all forums.',
        ],
        'numdeletedthreads' => [
            'name' => 'mybb_stats_num_deleted_threads',
	        'help' => 'The number of deleted threads in all forums.',
        ],
        'numposts' => [
            'name' => 'mybb_stats_num_posts',
	        'help' => 'The number of posts in all forums.',
        ],
        'numunapprovedposts' => [
            'name' => 'mybb_stats_num_unapproved_posts',
	        'help' => 'The number of unapproved posts in all forums.',
        ],
        'numdeletedposts' => [
            'name' => 'mybb_stats_num_deleted_posts',
	        'help' => 'The number of deleted posts in all forums.',
        ],
        'numusers' => [
            'name' => 'mybb_stats_num_users',
	        'help' => 'The number of registered users.',
        ],
        'lastuid' => [
            'name' => 'mybb_stats_last_user_id',
	        'help' => 'The ID of the last registered user.',
        ],
    ];

    private static $calculatedStats = [
    	'replies_per_thread' => [
			'name' => 'mybb_stats_replies_per_thread',
		    'help' => 'The average number of replies per thread.',
	    ],
	    'posts_per_member' => [
		    'name' => 'mybb_stats_posts_per_member',
		    'help' => 'The average number of posts per member.',
	    ],
	    'threads_per_member' => [
		    'name' => 'mybb_stats_threads_per_member',
		    'help' => 'The average number of threads per member.',
	    ],
	    'posts_per_day' => [
		    'name' => 'mybb_stats_posts_per_day',
		    'help' => 'The average number of posts per day.',
	    ],
	    'threads_per_day' => [
		    'name' => 'mybb_stats_threads_per_day',
		    'help' => 'The average number of threads per day.',
	    ],
	    'members_per_day' => [
		    'name' => 'mybb_stats_members_per_day',
		    'help' => 'The average number of members per day.',
	    ],
    ];

	/**
	 * @var \MyBB
	 */
	protected $mybb;

	/**
	 * @var \DB_Base
	 */
	protected $db;

	public function __construct(MyBB $mybb, DB_Base $db, datacache $cache)
	{
		parent::__construct($cache);

		$this->mybb = $mybb;
		$this->db = $db;
	}

	/**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'board_stats';
    }

    /**
     * @inheritDoc
     */
    function getMetrics(): array
    {
        $metrics = [];

        $statsCache = $this->readCache($this->cache, 'stats');

        foreach (static::$gaugeStats as $key => $stat) {
            if (isset($statsCache[$key])) {
                $metrics[$stat['name']] = (new Metric($stat['name'], Metric::TYPE_GAUGE))
                    ->setHelp($stat['help'])
                    ->setValue((int) $statsCache[$key]);
            }
        }

        $calculatedStats = [];

        if (isset($statsCache['numposts']) && isset($statsCache['numthreads'])) {
        	$calculatedStats['replies_per_thread'] = round(
        		(($statsCache['numposts'] - $statsCache['numthreads']) / $statsCache['numthreads']),
		        2
	        );
        }

        if (isset($statsCache['numposts']) && isset($statsCache['numusers'])) {
        	$calculatedStats['posts_per_member'] = round(
        		($statsCache['numposts'] / $statsCache['numusers']),
		        2
	        );
        }

        if (isset($statsCache['numthreads']) && isset($statsCache['numusers'])) {
        	$calculatedStats['threads_per_member'] = round(
        		($statsCache['numthreads'] / $statsCache['numusers']),
		        2
	        );
        }

	    // Get number of days since board start
	    $query = $this->db->simple_select('users', 'regdate', '', ['order_by' => 'regdate', 'limit' => 1]);
	    $result = (int) $this->db->fetch_field($query, 'regdate');
	    $days = (TIME_NOW - $result) / 86400;
	    if ($days < 1) {
		    $days = 1;
	    }

	    if (isset($statsCache['numposts'])) {
		    $calculatedStats['posts_per_day'] = round(($statsCache['numposts'] / $days), 2);
	    }

	    if (isset($statsCache['numthreads'])) {
	    	$calculatedStats['threads_per_day'] = round(($statsCache['numthreads'] / $days), 2);
	    }

	    if (isset($statsCache['numusers'])) {
	    	$calculatedStats['members_per_day'] = round(($statsCache['numusers'] / $days), 2);
	    }

        foreach (static::$calculatedStats as $key => $stat) {
        	if (isset($calculatedStats)) {
		        $metrics[$stat['name']] = (new Metric($stat['name'], Metric::TYPE_GAUGE))
			        ->setHelp($stat['help'])
			        ->setValue((int) $statsCache[$key]);
	        }
        }

		$this->getStatisticsMetrics($metrics);

        return $metrics;
    }

    private function getStatisticsMetrics(array &$metrics): void
    {
	    $statisticsCache = $this->cache->read('statistics');

	    $this->mybb->settings['statscachetime'] = (int)$this->mybb->settings['statscachetime'];
	    if($this->mybb->settings['statscachetime'] < 1)
	    {
		    $this->mybb->settings['statscachetime'] = 0;
	    }

	    $interval = $this->mybb->settings['statscachetime'] * 3600;

	    if(!$statisticsCache || $interval == 0 || TIME_NOW - $interval > $statisticsCache['time'])
	    {
		    $this->cache->update_statistics();
		    $statisticsCache = $this->cache->read('statistics');
	    }

	    $percentageOfUsersWhoHavePosted = round(
	    	(((int) $statisticsCache['posters'] / (int)$statisticsCache['numusers']) * 100),
		    2
	    );

	    $metrics['mybb_stats_percentage_of_users_who_have_posted'] =
		    (new Metric('mybb_stats_percentage_of_users_who_have_posted', Metric::TYPE_GAUGE))
		        ->setHelp('The percentage of users who have posted.')
		        ->setValue($percentageOfUsersWhoHavePosted)
		        ->setTimeStamp((int) $statisticsCache['time']);
    }
}