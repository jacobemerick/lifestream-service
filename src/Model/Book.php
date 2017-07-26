<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;

class Book
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
     * @param string $bookId
     * @return array
     */
    public function getBookByBookId($bookId)
    {
        $query = "
            SELECT `id`, `book_id`, `datetime`, `metadata`
            FROM `book`
            WHERE `book_id` = :book_id";

        $bindings = [
            'book_id' => $bookId,
        ];

        return $this->extendedPdo->fetchOne($query, $bindings);
    }

    /**
     * @param string $bookId
     * @param string $permalink
     * @param DateTime $datetime
     * @param string $metadata
     * @return boolean
     */
    public function insertBook($bookId, $permalink, DateTime $datetime, $metadata)
    {
        $query = "
            INSERT INTO `book` (`book_id`, `permalink`, `datetime`, `metadata`)
            VALUES (:book_id, :permalink, :datetime, :metadata)";

        $bindings = [
            'book_id' => $bookId,
            'permalink' => $permalink,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $insertBookCount = $this->extendedPdo->fetchAffected($query, $bindings);
        return $insertBookCount === 1;
    }
}
