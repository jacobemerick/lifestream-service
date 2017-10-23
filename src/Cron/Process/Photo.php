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

        foreach ($media as $mediaItem) {
            $event = $this->getEvent(
                $this->container->get('eventModel'),
                'photo',
                $mediaItem['id']
            );

            if (!$event) {
                $this->insertMedia($mediaItem);
                continue;
            }

            $mediaUpdated = $this->checkMediaUpdated($this->container->get('photoModel'), $mediaItem);
            if ($mediaUpdated) {
                $this->updateMedia($mediaItem);
                continue;
            }
        }

            $mediaItemMetadata = json_decode($mediaItem['metadata']);

            $description = $this->getDescription($mediaItemMetadata);
            $descriptionHtml = $this->getDescriptionHtml($mediaItemMetadata);

            // todo handle likes / comments
            try {
                $this->insertEvent(
                    $this->container->get('eventModel'),
                    $this->container->get('typeModel'),
                    $this->container->get('userModel'),
                    $description,
                    $descriptionHtml,
                    (new DateTime($mediaItem['datetime'])),
                    (object) [],
                    'Jacob Emerick',
                    'photo',
                    $mediaItem['id']
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Added photo event: {$mediaItem['id']}");
        }
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
    protected function insertMedia(array $media)
    {
        $mediaMetadata = json_decode($media['metadata']);

        $description = $this->getDescription($mediaMetadata);
        $descriptionHtml = $this->getDescriptionHtml($mediaMetadata);

        // todo handle likes / comments
        try {
            $this->insertEvent(
                $this->container->get('eventModel'),
                $this->container->get('typeModel'),
                $this->container->get('userModel'),
                $description,
                $descriptionHtml,
                (new DateTime($media['datetime'])),
                (object) [],
                'Jacob Emerick',
                'photo',
                $media['id']
            );
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }

        $this->logger->debug("Added photo event: {$media['id']}");
        return true;
    }

    /**
     * @param PhotoModel $photoModel
     * @param array $media
     * @return boolean
     */
    protected function checkMediaUpdated(PhotoModel $photoModel, array $media)
    {
        return false;
    }

    /**
     * @param array $media
     * @return boolean
     */
    protected function updateMedia(array $media)
    {
        return true;
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
