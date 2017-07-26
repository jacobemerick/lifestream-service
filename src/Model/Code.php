<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class Code
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
     * @param string $eventId
     * @return array
     */
    public function getEventByEventId($eventId)
    {
        $query = "
            SELECT `id`, `type`, `datetime`, `metadata`
            FROM `code`
            WHERE `event_id` = :event_id";

        $bindings = [
            'event_id' => $eventId,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @param string $eventId
     * @param string $type
     * @param DateTime $datetime
     * @param string $metadata
     * @return boolean
     */
    public function insertEvent($eventId, $type, DateTime $datetime, $metadata)
    {
        $query = "
            INSERT INTO `code` (`event_id`, `type`, `datetime`, `metadata`)
            VALUES (:event_id, :type, :datetime, :metadata)";

        $bindings = [
            'event_id' => $eventId,
            'type' => $type,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $insertEventCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertEventCount === 1;
    }
}
