<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class Twitter
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
     * @param string $tweetId
     * @return array
     */
    public function getTweetByTweetId($tweetId)
    {
        $query = "
            SELECT `id`, `tweet_id`, `datetime`, `metadata`
            FROM `twitter`
            WHERE `tweet_id` = :tweet_id";

        $bindings = [
            'tweet_id' => $tweetId,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @param string $tweetId
     * @param DateTime $datetime
     * @param string $metadata
     * @return boolean
     */
    public function insertTweet($tweetId, DateTime $datetime, $metadata)
    {
        $query = "
            INSERT INTO `twitter` (`tweet_id`, `datetime`, `metadata`)
            VALUES (:tweet_id, :datetime, :metadata)";

        $bindings = [
            'tweet_id' => $tweetId,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $insertTweetCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertTweetCount === 1;
    }
}
