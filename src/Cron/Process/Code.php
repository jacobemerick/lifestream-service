<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Code as CodeModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Code implements CronInterface, LoggerAwareInterface
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
            $events = $this->fetchEvents($this->container->get('codeModel'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        foreach ($events as $event) {
            $event = $this->getEvent(
                $this->container->get('eventModel'),
                'code',
                $event['id']
            );

            if ($event) {
                continue;
            }

            $eventMetadata = json_decode($event['metadata']);

            try {
                [ $description, $descriptionHtml ] = $this->getDescriptions($event['type'], $eventMetadata);
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
                    (new DateTime($event['datetime'])),
                    (object) [],
                    'Jacob Emerick',
                    'code',
                    $event['id']
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Added code event: {$event['id']}");
        }
    }

    /**
     * @param CodeModel $codeModel
     * @return array
     */
    protected function fetchEvents(CodeModel $codeModel)
    {
        return $codeModel->getEvents();
    }

    /**
     * @param string $type
     * @param stdclass $metadata
     * @return array
     */
    protected function getDescriptions($type, stdclass $metadata)
    {
        switch ($type) {
            case 'CreateEvent':
                if (
                    $eventMetadata->payload->ref_type == 'branch' ||
                    $eventMetadata->payload->ref_type == 'tag'
                ) {
                    $description = $this->getCreateDescription($eventMetadata);
                    $descriptionHtml = $this->getCreateDescriptionHtml($eventMetadata);
                } else if ($eventMetadata->payload->ref_type == 'repository') {
                    $description = $this->getCreateRepositoryDescription($eventMetadata);
                    $descriptionHtml = $this->getCreateRepositoryDescription($eventMetadata);
                } else {
                    throw new Exception("Skipping create event: {$eventMetadata->payload->ref_type}");
                }
                break;
            case 'ForkEvent':
                $description = $this->getForkDescription($eventMetadata);
                $descriptionHtml = $this->getForkDescriptionHtml($eventMetadata);
                break;
            case 'PullRequestEvent':
                $description = $this->getPullRequestDescription($eventMetadata);
                $descriptionHtml = $this->getPullRequestDescriptionHtml($eventMetadata);
                break;
            case 'PushEvent':
                $description = $this->getPushDescription($eventMetadata);
                $descriptionHtml = $this->getPushDescriptionHtml($eventMetadata);
                break;
            default:
                throw new Exception("Skipping an event type: {$event['type']}");
                break;
        }

        return [ $description, $descriptionHtml];
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getCreateDescription(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getCreateDescriptionHtml(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getCreateRepositoryDescription(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getCreateRepositoryDescriptionHtml(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getForkDescription(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getForkDescriptionHtml(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getPullRequestDescription(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getPullRequestDescriptionHtml(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getPushDescription(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getPushDescriptionHtml(stdclass $metadata)
    {
        return 'wrote some code';
    }
}
