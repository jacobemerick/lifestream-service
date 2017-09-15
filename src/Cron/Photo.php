<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use stdClass;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Photo as PhotoModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Photo implements CronInterface, LoggerAwareInterface
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
        $maxId = null;

        while (true) {
            try {
                $media = $this->fetchMedia($this->container->get('photoClient'), $maxId);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }
var_dump($media); exit;
            if (empty($media)) {
                break;
            }

            $this->logger->debug("Processing page {$page} of api results");

            foreach ($media as $item) {
                $itemExists = $this->checkEntryExists($this->container->get('photoModel'), $item->id);
                if ($itemExists) {
                    continue;
                }

                try {
                    $this->insertEntry(
                        $this->container->get('photoModel'),
                        $item,
                        $this->container->get('timezone')
                    );
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    return;
                }

                $this->logger->debug("Inserted new photo item: {$item->id}");
            }

            $page++;
        }
    }

    /**
     * @param Client $client
     * @param integer $maxId
     * @return array
     */
    protected function fetchMedia(Client $client, $maxId = null)
    {
        $response = $client->request(
            'GET',
            "users/self/media/recent"
        );
/*,
            [
                'query' => [
                    'count' => 50,
                ],
            ]
        );
*/
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Error while trying to fetch media: {$response->getStatusCode()}");
        }

        $jsonString = (string) $response->getBody();
        $json = json_decode($jsonString);
        return $json;
    }

    /**
     * @param PhotoModel $photoModel
     * @param string $itemId
     * @return boolean
     */
    protected function checkEntryExists(PhotoModel $photoModel, $itemId)
    {
        $item = $photoModel->getEntryByEntryId($itemId);
        return $item !== false;
    }

    /**
     * @param PhotoModel $photoModel
     * @param stdClass $item
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertEntry(PhotoModel $photoModel, stdClass $item, DateTimeZone $timezone)
    {
        $datetime = new DateTime($item->at);
        $datetime->setTimezone($timezone);

        $result = $photoModel->insertEntry(
            $item->id,
            $item->workout->activity_type,
            $datetime,
            json_encode($item)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new item: {$item->id}");
        }

        return true;
    }
}
