<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;

class Type
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
     * @return array
     */
    public function getTypes()
    {
        $query = "
            SELECT `id`, `name`
            FROM `type`
            ORDER BY `name` ASC";

        return $this->extendedPdo->fetchAll($query);
    }

    /**
     * @param string $type
     * @return integer
     */
    public function getTypeId($type)
    {
        $query = "
            SELECT `id`
            FROM `type`
            WHERE `name` = :type
            LIMIT 1";

        $bindings = [
            'type' => $type,
        ];

        return $this->extendedPdo->fetchValue($query, $bindings);
    }
}
