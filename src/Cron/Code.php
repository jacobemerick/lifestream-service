<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;

use GuzzleHttp\ClientInterface as Client;
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

        while ($makeNewRequest) {
            try {
                $events = $this->fetchEvents($this->container->get('codeClient'));
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $makeNewRequest = false;
            $this->logger->debug("Processing page {$page} of api results");

            foreach ($events as $event) {
                $eventExists = $this->checkEventExists($this->container->get('codeModel'), $event->event_id);
                if ($eventExists) {
                    $makeNewRequest = false;
                    continue;
                }

                $makeNewRequest = true;
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

                $this->logger->debug("Inserted new event: {$event->event_id}");
            }

            $page++;
        }
    }

    /**
     * @param Client $client
     * @return array
     */
    protected function fetchEvents(Client $client)
    {
        $response = $client->request('GET', 'some-endpoint');
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Error while trying to fetch events: {$response->getStatusCode()}");
        }

        $jsonString = (string) $response->getBody();
        $json = json_decode($jsonString);
        return $json->events;
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
     * @param stdclass $event
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertEvent(CodeModel $codeModel, stdclass $event, DateTimeZone $timezone)
    {
        $datetime = new DateTime($event->created_at);
        $datetime->setTimezone($timezone);

        $result = $codeModel->insertEvent(
            $event->event_id,
            $datetime,
            json_encode($event)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new event: {$event->event_id}");
        }

        return true;
    }
}
