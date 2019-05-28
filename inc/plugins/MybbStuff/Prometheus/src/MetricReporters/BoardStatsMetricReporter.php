<?php
declare(strict_types=1);

namespace MybbStuff\Prometheus\MetricReporters;

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

        if (isset($statsCache['lastusername'])) {
            $metrics['mybb_last_user_name'] = (new Metric('mybb_last_user_name', Metric::TYPE_UNTYPED))
                ->setHelp('The username of the last registered user')
                ->setValue($statsCache['lastusername']);
        }

        return $metrics;
    }
}