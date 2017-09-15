<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class Photo
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
     * @param string $mediaId
     * @return array
     */
    public function getMediaByMediaId($mediaId)
    {
        $query = "
            SELECT `id`, `datetime`, `metadata`
            FROM `photo`
            WHERE `media_id` = :media_id";

        $bindings = [
            'media_id' => $mediaId,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @param string $mediaId
     * @param DateTime $datetime
     * @param string $metadata
     * @return boolean
     */
    public function insertMedia($mediaId, DateTime $datetime, $metadata)
    {
        $query = "
            INSERT INTO `photo` (`media_id`, `datetime`, `metadata`)
            VALUES (:media_id, :datetime, :metadata)";

        $bindings = [
            'media_id' => $mediaId,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $insertMediaCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertMediaCount === 1;
    }

    /**
     * @param string $mediaId
     * @param string $metadata
     * @return boolean
     */
    public function updateMedia($mediaId, $metadata)
    {
        $query = "
            UPDATE `photo`
            SET `metadata` = :metadata
            WHERE `media_id` = :media_id
            LIMIT 1";

        $bindings = [
            'media_id' => $mediaId,
            'metadata' => $metadata,
        ];

        $updateMediaCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $updateMediaCount === 1;
    }
}
