<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;

class User
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
     * @param string $user
     * @return integer
     */
    public function getUserId($user)
    {
        $query = "
            SELECT `id`
            FROM `user`
            WHERE `user`.`name` = :user
            LIMIT 1";

        $bindings = [
            'user' => $user,
        ];

        return $this->extendedPdo->fetchValue($query, $bindings);
    }
}
