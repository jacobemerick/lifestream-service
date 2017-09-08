<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use stdClass;

use Madcoda\Youtube as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Video as VideoModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Video implements CronInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

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
            $playlist = $this->container->get('config')->youtube->playlist;
            $videos = $this->fetchEntries($this->container->get('videoClient'), $playlist);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        $this->logger->debug("Processing videos from api result");

        foreach ($videos as $video) {
            $videoExists = $this->checkVideoExists($this->container->get('videoModel'), $video->videoId)
            if ($videoExists) {
                continue;
            }

            try {
                $this->insertEntry(
                    $this->container->get('videoModel'),
                    $video,
                    $this->container->get('timezone')
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Inserted new video: {$video->videoId}");
        }
    }

    /**
     * @param Client $client
     * @param string $playlist
     * @return array
     */
    protected function fetchVideos(Client $client, $playlist)
    {
        return $client->getPlaylistItemsByPlaylistId($playlist);
    }

    /**
     * @param VideoModel $videoModel
     * @param string $videoId
     * @return boolean
     */
    protected function checkVideoExists(VideoModel $videoModel, $videoId)
    {
        $video = $videoModel->getVideoByVideoId($videoId);
        return $video !== false;
    }

    /**
     * @param VideoModel $videoModel
     * @param stdClass $video
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertVideo(VideoModel $videoModel, stdClass $video, DateTimeZone $timezone)
    {
        $datetime = new DateTime($entry->snippet->publishedAt);
        $datetime->setTimezone($timezone);

        $result = $videoModel->insertVideo(
            $video->videoId,
            $datetime,
            json_encode($video)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new video: {$video->videoId}");
        }

        return true;
    }
}
