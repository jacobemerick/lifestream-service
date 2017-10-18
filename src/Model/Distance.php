<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class Distance
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
     * @param string $entryId
     * @return array
     */
    public function getEntryByEntryId($entryId)
    {
        $query = "
            SELECT `id`, `type`, `datetime`, `metadata`
            FROM `distance`
            WHERE `entry_id` = :entry_id";

        $bindings = [
            'entry_id' => $entryId,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @return array
     */
    public function getEntries()
    {
        $query = "
            SELECT `id`, `entry_id`, `type`, `datetime`, `metadata`
            FROM `distance`";

        return $this->extendedPdo->fetchAll($query);
    }
 
    /**
     * @param string $entryId
     * @param string $type
     * @param DateTime $datetime
     * @param string $metadata
     * @return boolean
     */
    public function insertEntry($entryId, $type, DateTime $datetime, $metadata)
    {
        $query = "
            INSERT INTO `distance` (`entry_id`, `type`, `datetime`, `metadata`)
            VALUES (:entry_id, :type, :datetime, :metadata)";

        $bindings = [
            'entry_id' => $entryId,
            'type' => $type,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $insertEntryCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertEntryCount === 1;
    }
}
