<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{

    public function testIsInstanceOfBook()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Book($mockPdo);

        $this->assertInstanceOf(Book::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Book($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetBookByBookIdSendsParams()
    {
        $bookId = '123';

        $query = "
            SELECT `id`, `book_id`, `datetime`, `metadata`
            FROM `book`
            WHERE `book_id` = :book_id";
        $bindings = [
            'book_id' => $bookId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Book($mockPdo);
        $model->getBookByBookId($bookId);
    }

    public function testGetBookByBookIdReturnsBook()
    {
        $book = [
            'id' => 1,
            'book_id' => '123',
            'datetime' => '2016-06-30 12:00:00',
            'metadata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($book);

        $model = new Book($mockPdo);
        $result = $model->getBookByBookId('');

        $this->assertSame($book, $result);
    }

    public function testInsertBookSendsParams()
    {
        $bookId = '123';
        $permalink = 'http://site.com/some-book';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $query = "
            INSERT INTO `book` (`book_id`, `permalink`, `datetime`, `metadata`)
            VALUES (:book_id, :permalink, :datetime, :metadata)";
        $bindings = [
            'book_id' => $bookId,
            'permalink' => $permalink,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAffected')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Book($mockPdo);
        $model->insertBook($bookId, $permalink, $datetime, $metadata);
    }

    public function testInsertBookReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Book($mockPdo);
        $result = $model->insertBook('', '', new DateTime(), '');

        $this->assertTrue($result);
    }

    public function testInsertBookReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Book($mockPdo);
        $result = $model->insertBook('', '', new DateTime(), '');

        $this->assertFalse($result);
    }
}
