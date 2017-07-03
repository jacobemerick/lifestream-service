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
}
