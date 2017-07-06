<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;

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
            INNER JOIN `type` ON `type`.`id` = `event`.`type`
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
}
