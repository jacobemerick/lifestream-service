<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Code as CodeModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Code implements CronInterface, LoggerAwareInterface
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
        $page = 1;

        $client = $this->container->get('codeClient');
        $pager = $this->container->get('codeClientPager');
        $userApi = $client->api('user');

        $username = $this->container->get('config')->code->username;
        try {
            $events = $pager->fetch($userApi, 'publicEvents', [ $username ]);
            $this->logger->debug("Processing page {$page} of api results");
            $this->processEvents($events);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        while ($pager->hasNext()) {
            $page++;

            try {
                $events = $pager->fetchNext();
                $this->logger->debug("Processing page {$page} of api results");
                $this->processEvents($events);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }
        }
    }

    /**
     * @param array $events
     */
    protected function processEvents(array $events)
    {
        foreach ($events as $event) {
            $eventExists = $this->checkEventExists($this->container->get('codeModel'), $event['id']);
            if ($eventExists) {
                continue;
            }

            $this->insertEvent($this->container->get('codeModel'), $event, $this->container->get('timezone'));
            $this->logger->debug("Inserted new event: {$event['id']}");
        }
    }

    /**
     * @param CodeModel $codeModel
     * @param string $eventId
     * @return boolean
     */
    protected function checkEventExists(CodeModel $codeModel, $eventId)
    {
        $event = $codeModel->getEventByEventId($eventId);
        return $event !== false;
    }

    /**
     * @param CodeModel $codeModel
     * @param array $event
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertEvent(CodeModel $codeModel, array $event, DateTimeZone $timezone)
    {
        $datetime = new DateTime($event['created_at']);
        $datetime->setTimezone($timezone);

        $result = $codeModel->insertEvent(
            $event['id'],
            $event['type'],
            $datetime,
            json_encode($event)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new event: {$event['id']}");
        }

        return true;
    }
}
