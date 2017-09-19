<?php

namespace Jacobemerick\LifestreamService\Cron\Fetch;

use DateTime;
use DateTimeZone;
use Exception;
use stdClass;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
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
                try {
                    $this->processMedia($this->container->get('photoModel'), $media);
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    return;
                }
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
    protected function fetchMedia(Client $client, $token, $maxId)
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
     * @param stdclass $media
     * @return boolean
     */
    protected function processMedia(PhotoModel $photoModel, stdclass $media)
    {
        $mediaExists = $this->checkMediaExists($photoModel, $media->id);
        if (!$mediaExists) {
            $this->insertMedia($photoModel, $media, $this->container->get('timezone'));
            $this->logger->debug("Inserted new media: {$media->id}");
            return true;
        }

        $mediaUpdated = $this->checkMediaUpdated($photoModel, $media->id, $media);
        if ($mediaUpdated) {
            $this->updateMedia($photoModel, $media->id, $media);
            $this->logger->debug("Updated media: {$media->id}");
            return true;
        }

        return false;
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
        $datetime = new DateTime("@{$media->created_time}");
        $datetime->setTimezone($timezone);

        $result = $photoModel->insertMedia(
            $media->id,
            $datetime,
            json_encode($media)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new media: {$media->id}");
        }

        return true;
    }

    /**
     * @param PhotoModel $photoModel
     * @param string $mediaId
     * @param stdclass $media
     * @return boolean
     */
    protected function checkMediaUpdated(PhotoModel $photoModel, $mediaId, stdclass $media)
    {
        $metadata = $photoModel->getMediaByMediaId($mediaId)['metadata'];
        $metadata = json_decode($metadata);

        if ($metadata->likes->count != $media->likes->count) {
            return true;
        }
        if ($metadata->comments->count != $media->comments->count) {
            return true;
        }

        return false;
    }

    /**
     * @param PhotoModel $photoModel
     * @param string $mediaId
     * @param stdclass $media
     * @return boolean
     */
    protected function updateMedia(PhotoModel $photoModel, $mediaId, stdclass $media)
    {
        $result = $photoModel->updateMedia(
            $mediaId,
            json_encode($media)
        );
        if (!$result) {
            throw new Exception("Error while trying to update media: {$mediaId}");
        }
        return true;
    }
}
