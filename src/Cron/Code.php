<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;

use Github\Client;
use Github\ResultPager as Pager;
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
        $makeNewRequest = true;

        $client = $this->container->get('codeClient');
        $userApi = $client->api('user');
        $pager = new Pager($client);

        // @todo how does this api fail? what does it look like?
        $events = $pager->fetch($userApi, 'publicEvents', [ 'jacobemerick' ]);
        $this->processEvents($events, $page);

        while ($pager->hasNext()) {
            $page++;
            $events = $pager->fetchNext();
            $this->processEvents($events, $page);
        }
    }

    /**
     * @param array $events
     * @param integer $page
     */
    protected function processEvents(array $events, $page)
    {
        // @todo should this log be here or in ::run
        $this->logger->debug("Processing page {$page} of api results");

        foreach ($events as $event) {
            $eventExists = $this->checkEventExists($this->container->get('codeModel'), $event['id']);
            if ($eventExists) {
                continue;
            }

            // @todo allow exception to bubble up
            try {
                $this->insertEvent(
                    $this->container->get('codeModel'),
                    $event,
                    $this->container->get('timezone')
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

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
