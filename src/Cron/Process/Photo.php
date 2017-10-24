<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Photo as PhotoModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Photo implements CronInterface, LoggerAwareInterface
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
            $media = $this->fetchMedia($this->container->get('photoModel'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        array_walk($media, [ $this, 'processMedia' ]);
    }

    /**
     * @param PhotoModel $photoModel
     * @return array
     */
    protected function fetchMedia(PhotoModel $photoModel)
    {
        return $photoModel->getMedia();
    }


    /**
     * @param array $media
     * @return boolean
     */
    protected function processMedia(array $media)
    {
        $event = $this->getEvent(
            $this->container->get('eventModel'),
            'photo',
            $media['id']
        );

        $metadata = $this->getMediaMetadata($media);

        if (!$event) {
            try {
                $this->insertMedia($media, $metadata);
            } catch (Exception $exception) {
                $this->logger->error($exception);
                return false;
            }

            $this->logger->debug("Added photo event: {$media['id']}");
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
                $this->logger->error($exception);
                return false;
            }

            $this->logger->debug("Updated photo event metadata: {$media['id']}");
            return true;
        }

        return false;
    }

    /**
     * @param array $media
     * @return stdclass
     */
    protected function getMediaMetadata(array $media)
    {
        $metadata = json_decode($media['metadata']);

        return (object) [
            'likes' => $metadata->likes->count,
            'comments' => $metadata->comments->count,
        ];
    }

    /**
     * @param array $media
     * @param stdclass $metadata
     * @return boolean
     */
    protected function insertMedia(array $media, stdclass $metadata)
    {
        $mediaMetadata = json_decode($media['metadata']);

        $description = $this->getDescription($mediaMetadata);
        $descriptionHtml = $this->getDescriptionHtml($mediaMetadata);

        return $this->insertEvent(
            $this->container->get('eventModel'),
            $this->container->get('typeModel'),
            $this->container->get('userModel'),
            $description,
            $descriptionHtml,
            (new DateTime($media['datetime'])),
            $metadata,
            'Jacob Emerick',
            'photo',
            $media['id']
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
     * @param string $text
     * @return string
     */
    protected function simpleText($text)
    {
        $text = strtok($text, "\n");
        $text = trim($text);
        return $text;
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescription(stdclass $metadata)
    {
        return sprintf(
            'Shared a photo | %s',
            $this->simpleText($metadata->caption->text)
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
            '<img src="%s" alt="%s" height="%d" width="%d" />',
            $metadata->images->standard_resolution->url,
            $this->simpleText($metadata->caption->text),
            $metadata->images->standard_resolution->height,
            $metadata->images->standard_resolution->width
        );

        $description .= sprintf(
            '<p>%s</p>',
            $this->simpleText($metadata->caption->text)
        );

        // todo handle hashtags

        return $description;
    }
}
