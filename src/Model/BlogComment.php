<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class BlogComment
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
    public function getCommentByPermalink($permalink)
    {
        $query = "
            SELECT `id`, `permalink`, `datetime`, `metadata`
            FROM `blog_comment`
            WHERE `permalink` = :permalink";

        $bindings = [
            'permalink' => $permalink,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @param string $permalink
     * @return array
     */
    public function getCommentCountByPage($permalink)
    {
        $query = "
            SELECT COUNT(1)
            FROM `blog_comment`
            WHERE `permalink` LIKE :permalink";

        $bindings = [
            'permalink' => "{$permalink}%",
        ];

        return $this->extendedPdo->fetchValue($query, $bindings);
    }

    /**
     * @param string $permalink
     * @param DateTime $datetime
     * @param string $metadata
     * @return boolean
     */
    public function insertComment($permalink, DateTime $datetime, $metadata)
    {
        $query = "
            INSERT INTO `blog_comment` (`permalink`, `datetime`, `metadata`)
            VALUES (:permalink, :datetime, :metadata)";

        $bindings = [
            'permalink' => $permalink,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $insertCommentCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertCommentCount === 1;
    }
}
