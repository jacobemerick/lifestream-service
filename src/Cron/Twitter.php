<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
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
                $maxId = $tweet->id_str;
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
        $tweetExists = $this->checkTweetExists($twitterModel, $tweet->id_str);
        if (!$tweetExists) {
            $this->insertTweet($twitterModel, $tweet, $this->container->get('timezone'));
            $this->logger->debug("Inserted new tweet: {$tweet->id_str}");
            return true;
        }

        $tweetUpdated = $this->checkTweetMetadata($twitterModel, $tweet->id_str, $tweet);
        if ($tweetUpdated) {
            $this->updateTweet($twitterModel, $tweet->id_str, $tweet);
            $this->logger->debug("Updated tweet: {$tweet->id_str}");
            return true;
        }

        return false;
    }

    /**
     * @param TwitterModel $twitterModel
     * @param string $tweetId
     * @return boolean
     */
    protected function checkTweetExists(TwitterModel $twitterModel, $tweetId)
    {
        $tweet = $twitterModel->getTweetByTweetId($tweetId);
        return $tweet !== false;
    }

    /**
     * @param TwitterModel $twitterModel
     * @param stdclass $tweet
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertTweet(TwitterModel $twitterModel, stdclass $tweet, DateTimeZone $timezone)
    {
        $datetime = new DateTime($tweet->created_at);
        $datetime->setTimezone($timezone);

        $result = $twitterModel->insertTweet(
            $tweet->id_str,
            $datetime,
            json_encode($tweet)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new tweet: {$tweet->id_str}");
        }

        return true;
    }

    /**
     * @param TwitterModel $twitterModel
     * @param string $tweetId
     * @param stdclass $tweet
     * @return boolean
     */
    protected function checkTweetMetadata(TwitterModel $twitterModel, $tweetId, stdclass $tweet)
    {
        $metadata = $twitterModel->getTweetByTweetId($tweetId)['metadata'];
        return $metadata !== json_encode($tweet);
    }

    /**
     * @param TwitterModel $twitterModel
     * @param string $tweetId
     * @param stdclass $tweet
     * @return boolean
     */
    protected function updateTweet(TwitterModel $twitterModel, $tweetId, stdclass $tweet)
    {
        $result = $twitterModel->updateTweet(
            $tweetId,
            json_encode($tweet)
        );

        if (!$result) {
            throw new Exception("Error while trying to update tweet: {$tweetId}");
        }

        return true;
    }
}
