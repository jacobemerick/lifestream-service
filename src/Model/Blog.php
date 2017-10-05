<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class Blog
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
     * @param string $permalink
     * @return array
     */
    public function getPostByPermalink($permalink)
    {
        $query = "
            SELECT `id`, `permalink`, `datetime`, `metadata`
            FROM `blog`
            WHERE `permalink` = :permalink";

        $bindings = [
            'permalink' => $permalink,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @return array
     */
    public function getPosts()
    {
        $query = "
            SELECT `id`, `permalink`, `datetime`, `metadata`
            FROM `blog`";

        return $this->extendedPdo->fetchAll($query);
    }

    /**
     * @param string $permalink
     * @param DateTime $datetime
     * @param string $metadata
     * @return boolean
     */
    public function insertPost($permalink, DateTime $datetime, $metadata)
    {
        $query = "
            INSERT INTO `blog` (`permalink`, `datetime`, `metadata`)
            VALUES (:permalink, :datetime, :metadata)";

        $bindings = [
            'permalink' => $permalink,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $insertPostCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertPostCount === 1;
    }
}
