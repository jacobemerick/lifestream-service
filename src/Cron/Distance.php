<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use stdClass;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Distance as DistanceModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Distance implements CronInterface, LoggerAwareInterface
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
                $username = $this->container->get('config')->distance->username;
                $entries = $this->fetchEntries($this->container->get('distanceClient'), $username, $page);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $makeNewRequest = false;
            $this->logger->debug("Processing page {$page} of api results");

            foreach ($entries as $entry) {
                $entryExists = $this->checkEntryExists($this->container->get('distanceModel'), $entry->id);
                if ($entryExists) {
                    $makeNewRequest = false;
                    continue;
                }

                $makeNewRequest = true;
                try {
                    $this->insertEntry(
                        $this->container->get('distanceModel'),
                        $entry,
                        $this->container->get('timezone')
                    );
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    return;
                }

                $this->logger->debug("Inserted new distance entry: {$entry->id}");
            }

            $page++;
        }
    }

    /**
     * @param Client $client
     * @param string $username
     * @param integer $page
     * @return array
     */
    protected function fetchEntries(Client $client, $username, $page)
    {
        $response = $client->request(
            'GET',
            "people/{$username}/entries.json",
            [
                'query' => [ 'page' => $page ],
            ]
        );
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Error while trying to fetch entries: {$response->getStatusCode()}");
        }

        $jsonString = (string) $response->getBody();
        $json = json_decode($jsonString);
        return $json->entries;
    }

    /**
     * @param DistanceModel $distanceModel
     * @param string $entryId
     * @return boolean
     */
    protected function checkEntryExists(DistanceModel $distanceModel, $entryId)
    {
        $entry = $distanceModel->getEntryByEntryId($entryId);
        return $entry !== false;
    }

    /**
     * @param DistanceModel $distanceModel
     * @param stdClass $entry
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertEntry(DistanceModel $distanceModel, stdClass $entry, DateTimeZone $timezone)
    {
        $datetime = new DateTime($entry->at);
        $datetime->setTimezone($timezone);

        $result = $distanceModel->insertEntry(
            $entry->id,
            $entry->workout->activity_type,
            $datetime,
            json_encode($entry)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new entry: {$entry->id}");
        }

        return true;
    }
}
