<?php

namespace Jacobemerick\LifestreamService\Cron\Fetch;

use DateTime;
use DateTimeZone;
use Exception;
use stdClass;

use Madcoda\Youtube\Youtube as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
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
            $playlist = $this->container->get('config')->video->playlist;
            $videos = $this->fetchVideos($this->container->get('videoClient'), $playlist);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        $this->logger->debug("Processing videos from api result");

        foreach ($videos as $video) {
            $videoExists = $this->checkVideoExists(
                $this->container->get('videoModel'),
                $video->contentDetails->videoId
            );
            if ($videoExists) {
                continue;
            }

            try {
                $this->insertVideo(
                    $this->container->get('videoModel'),
                    $video,
                    $this->container->get('timezone')
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Inserted new video: {$video->contentDetails->videoId}");
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
        $datetime = new DateTime($video->snippet->publishedAt);
        $datetime->setTimezone($timezone);

        $result = $videoModel->insertVideo(
            $video->contentDetails->videoId,
            $datetime,
            json_encode($video)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new video: {$video->contentDetails->videoId}");
        }

        return true;
    }
}
