<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Video as VideoModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Video implements CronInterface, LoggerAwareInterface
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
            $videos = $this->fetchVideos($this->container->get('videoModel'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        array_walk($videos, [ $this, 'processVideo' ]);
    }

    /**
     * @param VideoModel $videoModel
     * @return array
     */
    protected function fetchVideos(VideoModel $videoModel)
    {
        return $videoModel->getVideos();
    }


    /**
     * @param array $video
     * @return boolean
     */
    protected function processVideo(array $video)
    {
        $event = $this->getEvent(
            $this->container->get('eventModel'),
            'video',
            $video['id']
        );

        if ($event) {
            return false;
        }

        try {
            $this->insertVideo($video);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }

        $this->logger->debug("Added video event: {$video['id']}");
        return true;
    }

    /**
     * @param array $video
     * @return boolean
     */
    protected function insertVideo(array $video)
    {
        $metadata = json_decode($video['metadata']);

        $description = $this->getDescription($metadata);
        $descriptionHtml = $this->getDescriptionHtml($metadata);

        return $this->insertEvent(
            $this->container->get('eventModel'),
            $this->container->get('typeModel'),
            $this->container->get('userModel'),
            $description,
            $descriptionHtml,
            (new DateTime($video['datetime'])),
            (object) [],
            'Jacob Emerick',
            'video',
            $video['id']
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescription(stdclass $metadata)
    {
        return sprintf(
            'Favorited %s on Youtube',
            $metadata->snippet->title
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescriptionHtml(stdclass $metadata)
    {
        $description = '';

        $description .= sprintf(
            '<iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe>',
            $metadata->contentDetails->videoId
        );

        $description .= sprintf(
            '<p>Favorited <a href="https://youtu.be/%s" target="_blank" title="YouTube | %s">%s</a> on YouTube.</p>',
            $metadata->contentDetails->videoId,
            $metadata->snippet->title,
            $metadata->snippet->title
        );

        return $description;
    }
}
