<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Distance as DistanceModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Distance implements CronInterface, LoggerAwareInterface
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
            $entries = $this->fetchEntries($this->container->get('distanceModel'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        foreach ($entries as $entry) {
            $event = $this->getEvent(
                $this->container->get('eventModel'),
                'distance',
                $entry['id']
            );

            if ($event) {
                continue;
            }

            $entryMetadata = json_decode($entry['metadata']);

            try {
                [ $description, $descriptionHtml ] = $this->getDescriptions(
                    $entry['type'],
                    $entryMetadata
                );
            } catch (Exception $exception) {
                $this->logger->debug($exception->getMessage());
                continue;
            }

            try {
                $this->insertEvent(
                    $this->container->get('eventModel'),
                    $this->container->get('typeModel'),
                    $this->container->get('userModel'),
                    $description,
                    $descriptionHtml,
                    (new DateTime($entry['datetime'])),
                    (object) [],
                    'Jacob Emerick',
                    'distance',
                    $entry['id']
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Added distance event: {$entry['id']}");
        }
    }

    /**
     * @param DistanceModel $distanceModel
     * @return array
     */
    protected function fetchEntries(DistanceModel $distanceModel)
    {
        return $distanceModel->getEntries();
    }

    /**
     * @param string $type
     * @param stdclass $metadata
     * @return array
     */
    protected function getDescriptions($type, stdclass $metadata)
    {
        switch ($type) {
            case 'Hiking':
                $description = $this->getHikingDescription($metadata);
                $descriptionHtml = $this->getHikingDescriptionHtml($metadata);
                break;
            case 'Running':
                $description = $this->getRunningDescription($metadata);
                $descriptionHtml = $this->getRunningDescriptionHtml($metadata);
                break;
            case 'Walking':
                $description = $this->getWalkingDescription($metadata);
                $descriptionHtml = $this->getWalkingDescriptionHtml($metadata);
                break;
            default:
                throw new Exception("Skipping an entry type: {$type}");
                break;
        }

        return [ $description, $descriptionHtml ];
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getHikingDescription(stdclass $metadata)
    {
        return sprintf(
            'Hiked %.2f %s and felt %s.',
            $metadata->workout->distance->value,
            $metadata->workout->distance->units,
            $metadata->workout->felt
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getHikingDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        $description .= sprintf(
            '<p>Hiked %.2f %s and felt %s.</p>',
            $metadata->workout->distance->value,
            $metadata->workout->distance->units,
            $metadata->workout->felt
        );

        if (isset($metadata->workout->title)) {
            $description .= sprintf(
                '<p>I was hiking up around the %s area.</p>',
                $metadata->workout->title
            );
        }

        return $description;
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getRunningDescription(stdclass $metadata)
    {
        return sprintf(
            'Ran %.2f %s and felt %s.',
            $metadata->workout->distance->value,
            $metadata->workout->distance->units,
            $metadata->workout->felt
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getRunningDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        $description .= sprintf(
            '<p>Ran %.2f %s and felt %s.</p>',
            $metadata->workout->distance->value,
            $metadata->workout->distance->units,
            $metadata->workout->felt
        );

        if (isset($metadata->message)) {
            $description .= sprintf(
                '<p>Afterwards, I was all like %s.</p>',
                $metadata->message
            );
        }

        return $description;
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getWalkingDescription(stdclass $metadata)
    {
        return sprintf(
            'Walked %.2f %s and felt %s.',
            $metadata->workout->distance->value,
            $metadata->workout->distance->units,
            $metadata->workout->felt
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getWalkingDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        $description .= sprintf(
            '<p>Walked %.2f %s and felt %s.</p>',
            $metadata->workout->distance->value,
            $metadata->workout->distance->units,
            $metadata->workout->felt
        );

        if (isset($metadata->message)) {
            $description .= sprintf(
                '<p>%s</p>',
                $metadata->message
            );
        }

        return $description;
    }
}
