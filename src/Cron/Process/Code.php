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

            try {
                $eventMetadata = json_decode($event['metadata']);
                $description = $this->getDescription($eventMetadata);
                $descriptionHtml = $this->getDescriptionHtml($eventMetadata);

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
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescription(stdclass $metadata)
    {
        return 'wrote some code';
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescriptionHtml(stdclass $metadata)
    {
        return 'wrote some code';
    }
}
