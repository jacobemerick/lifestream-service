<?php

namespace Jacobemerick\LifestreamService\Cron;

use Exception;

use Abraham\TwitterOAuth\TwitterOAuth as Client;
use Interop\Container\ContainerInterface as Container;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Twitter implements CronInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    /** @var Container */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->logger = new NullLogger;
    }

    public function run()
    {
        $page = 1;

        try {
            $tweets = $this->fetchTweets(
                $this->container->get('twitterClient'),
                $this->container->get('config')->twitter->screenname
            );
        } catch (Exception $e) {
            $this->logger->error($exception->getMessage());
            return;
        }

        if (empty($tweets)) {
            return;
        }

        $this->logger->debug("Processing page {$page} of tweets");

        // process tweets
        // repeat request w/ last id
    }

    protected function fetchTweets(Client $client, $screenname, $maxId = null)
    {
        $params = [
            'screen_name' => $screenname,
            'count' => 200,
            'trim_user' => true,
        ];
        if (!is_null($maxId)) {
            $params['max_id'] = $maxId;
        }

        $tweets = $client->get('statuses/user_timeline', $params);
        if ($client->getLastHttpCode() !== 200) {
            throw new Exception("Error with fetching tweets: {$client->getLastHttpCode()}");
        }
        return $tweets;
    }
}
