<?php

namespace Jacobemerick\LifestreamService\Cron;

use Exception;
use stdclass;

use Abraham\TwitterOAuth\TwitterOAuth as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Twitter as TwitterModel;
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
        $maxId = null;

        while (true) {
            try {
                $tweets = $this->fetchTweets(
                    $this->container->get('twitterClient'),
                    $this->container->get('config')->twitter->screenname,
                    $maxId
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            if (empty($tweets)) {
                break;
            }

            $this->logger->debug("Processing page {$page} of tweets");

            foreach ($tweets as $tweet) {
                $maxId = (string) $tweet->id;
                try {
                    $this->processTweet($this->container->get('twitterModel'), $tweet);
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    return;
                }
            }

            $page++;
        }
    }

    /**
     * @param Client $client
     * @param string $screenname
     * @param string $maxId
     * @return array
     */
    protected function fetchTweets(Client $client, $screenname, $maxId)
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

    /**
     * @param TwitterModel $twitterModel
     * @param stdclass $tweet
     * @return boolean
     */
    protected function processTweet(TwitterModel $twitterModel, stdclass $tweet)
    {
        // if tweet does not exist, insert and return
        // else if exist, check metadata and update if needed
        return true;
    }
}
