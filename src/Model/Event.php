<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class Event
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
     * @param integer $limit
     * @param integer $offset
     * @param string $type
     * @param string $user
     * @param string $order
     * @param boolean $isAscending
     * @return array
     */
    public function getEvents(
        $limit = 0,
        $offset = 0,
        $type = '',
        $user = '',
        $order = '',
        $isAscending = true
    ) {
        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE 1 = 1";

        $bindings = [];

        if ($type !== '') {
            $query .= " AND
                `type`.`name` = :type_name";
            $bindings['type_name'] = $type;
        }
        if ($user !== '') {
            $query .= " AND
                `user`.`name` = :user_name";
            $bindings['user_name'] = $user;
        }
        if ($order !== '') {
            $direction = ($isAscending) ? 'ASC' : 'DESC';
            $query .= "
                ORDER BY `{$order}` {$direction}";
        }
        if ($limit > 0) {
            $query .= "
                LIMIT {$offset}, {$limit}";
        }

        return $this->extendedPdo->fetchAll($query, $bindings);
    }

    /**
     * @param string $type
     * @param integer $typeId
     * @return array
     */
    public function getEventByTypeId($type, $typeId)
    {
        $query = "
            SELECT `event`.`id`
            FROM `event`
            INNER JOIN `type` ON
                `type`.`id` = `event`.`type_id` AND
                `type`.`name` = :type
            WHERE `type_lookup_id` = :type_id";

        $bindings = [
            'type' => $type,
            'type_id' => $typeId,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @param string $description
     * @param string $descriptionHtml
     * @param DateTime $datetime
     * @param string $metadata
     * @param integer $typeId
     * @param integer $typeLookupId
     * @return boolean
     */
    public function insertEvent(
        $description,
        $descriptionHtml,
        DateTime $datetime,
        $metadata,
        $typeId,
        $typeLookupId
    ) {
        $query = "
            INSERT INTO `event` (`description`, `description_html`, `datetime`, `metadata`,
                                 `type_id`, `type_lookup_id`)
            VALUES (:description, :description_html, :datetime, :metadata, :type_id, :type_lookup_id)";

        $bindings = [
            'description' => $description,
            'description_html' => $descriptionHtml,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
            'type_id' => $typeId,
            'type_lookup_id' => $typeLookupId,
        ];

        $insertEventCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertEventCount === 1;
    }
}
