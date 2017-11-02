<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Twitter as TwitterModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Twitter implements CronInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;
    use ProcessTrait;

    /** @var Container */
    protected $container;

    /**
     * @param Container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->logger = new NullLogger;
    }

    public function run()
    {
        try {
            $tweet = $this->fetchTweets($this->container->get('twitterModel'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        array_walk($tweet, [ $this, 'processTweet' ]);
    }

    /**
     * @param TwitterModel $twitterModel
     * @return array
     */
    protected function fetchTweets(TwitterModel $twitterModel)
    {
        return $twitterModel->getTweets();
    }


    /**
     * @param array $tweet
     * @return boolean
     */
    protected function processTweet(array $tweet)
    {
        $event = $this->getEvent(
            $this->container->get('eventModel'),
            'twitter',
            $tweet['id']
        );

        $metadata = $this->getTweetMetadata($tweet);

        // check if tweet is a reply - if so, ignore

        if (!$event) {
            try {
                $this->insertTweet($tweet, $metadata);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return false;
            }

            $this->logger->debug("Added twitter event: {$tweet['id']}");
            return true;
        }

        $isMetadataUpdated = $this->checkMetadataUpdated($event, $metadata);
        if ($isMetadataUpdated) {
            try {
                $this->updateEventMetadata(
                    $this->container->get('eventModel'),
                    $event['id'],
                    $metadata
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return false;
            }

            $this->logger->debug("Updated twitter event metadata: {$tweet['id']}");
            return true;
        }

        return false;
    }

    /**
     * @param array $tweet
     * @return stdclass
     */
    protected function getTweetMetadata(array $tweet)
    {
        $metadata = json_decode($tweet['metadata']);

        return (object) [
            'favorites' => $metadata->favorite_count,
            'retweets' => $metadata->retweet_count,
        ];
    }

    /**
     * @param array $tweet
     * @param stdclass $metadata
     * @return boolean
     */
    protected function insertTweet(array $tweet, stdclass $metadata)
    {
        $tweetMetadata = json_decode($tweet['metadata']);

        $description = $this->getDescription($tweetMetadata);
        $descriptionHtml = $this->getDescriptionHtml($tweetMetadata);

        return $this->insertEvent(
            $this->container->get('eventModel'),
            $this->container->get('typeModel'),
            $this->container->get('userModel'),
            $description,
            $descriptionHtml,
            (new DateTime($tweet['datetime'])),
            $metadata,
            'Jacob Emerick',
            'twitter',
            $tweet['id']
        );
    }

    /**
     * @param array $event
     * @param stdclass $metadata
     * @return boolean
     */
    protected function checkMetadataUpdated(array $event, stdclass $metadata)
    {
        $oldMetadata = json_decode($event['metadata']);

        return $oldMetadata != $metadata;
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescription(stdclass $metadata)
    {
        // todo text needs to be single line, encoded, and links/media replaced w/ display
        return sprintf(
            'Tweeted | %s',
            $metadata->text
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        // whole bunch of stuff
        return $description;
    }
}