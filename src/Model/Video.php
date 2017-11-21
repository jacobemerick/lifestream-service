<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class Video
{

    /** @var ExtendedPdo */
    protected $extendedPdo;

    /**
     * @param ExtendedPdo $extendedPdo
     */
    public function __construct(ExtendedPdo $extendedPdo)
    {
        $this->extendedPdo = $extendedPdo;
    }

    /**
     * @param string $videoId
     * @return array
     */
    public function getVideoByVideoId($videoId)
    {
        $query = "
            SELECT `id`, `datetime`, `metadata`
            FROM `video`
            WHERE `video_id` = :video_id";

        $bindings = [
            'video_id' => $videoId,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @return array
     */
    public function getVideos()
    {
        $query = "
            SELECT `id`, `video_id`, `datetime`, `metadata`
            FROM `video`";

        return $this->extendedPdo->fetchAll($query);
    }

    /**
     * @param string $videoId
     * @param DateTime $datetime
     * @param string $metadata
     * @return boolean
     */
    public function insertVideo($videoId, DateTime $datetime, $metadata)
    {
        $query = "
            INSERT INTO `video` (`video_id`, `datetime`, `metadata`)
            VALUES (:video_id, :datetime, :metadata)";

        $bindings = [
            'video_id' => $videoId,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $insertVideoCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertVideoCount === 1;
    }
}
