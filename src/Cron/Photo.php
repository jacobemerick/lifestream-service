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
                $token = $this->container->get('config')->photo->token;
                $mediaList = $this->fetchMedia($this->container->get('photoClient'), $token, $maxId);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            if (empty($mediaList)) {
                break;
            }

            $this->logger->debug("Processing page {$page} of api results");

            foreach ($mediaList as $media) {
                $maxId = $media->id;

                $mediaExists = $this->checkMediaExists($this->container->get('photoModel'), $media->id);
                if ($mediaExists) {
                    continue;
                }

                // todo updated (likes)

                try {
                    $this->insertMedia(
                        $this->container->get('photoModel'),
                        $media,
                        $this->container->get('timezone')
                    );
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    return;
                }

                $this->logger->debug("Inserted new photo media: {$media->id}");
            }

            $page++;
        }
    }

    /**
     * @param Client $client
     * @param string $token
     * @param integer $maxId
     * @return array
     */
    protected function fetchMedia(Client $client, $token, $maxId = null)
    {
        $queryParams = [
            'access_token' => $token,
        ];
        if ($maxId) {
            $queryParams['max_id'] = $maxId;
        }

        $response = $client->request(
            'GET',
            "users/self/media/recent",
            [ 'query' => $queryParams ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Error while trying to fetch media: {$response->getStatusCode()}");
        }

        $jsonString = (string) $response->getBody();
        $json = json_decode($jsonString);
        return $json->data;
    }

    /**
     * @param PhotoModel $photoModel
     * @param string $mediaId
     * @return boolean
     */
    protected function checkMediaExists(PhotoModel $photoModel, $mediaId)
    {
        $media = $photoModel->getMediaByMediaId($mediaId);
        return $media !== false;
    }

    /**
     * @param PhotoModel $photoModel
     * @param stdClass $media
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertMedia(PhotoModel $photoModel, stdClass $media, DateTimeZone $timezone)
    {
        $datetime = new DateTime($media->at);
        $datetime->setTimezone($timezone);

        $result = $photoModel->insertMedia(
            $media->id,
            $media->workout->activity_type,
            $datetime,
            json_encode($media)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new media: {$media->id}");
        }

        return true;
    }
}
