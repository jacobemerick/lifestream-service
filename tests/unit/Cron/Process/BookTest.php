<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use ReflectionClass;
use stdclass;

use PHPUnit\Framework\TestCase;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Book as BookModel;
use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class BookTest extends TestCase
{

    public function testIsInstanceOfBook()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Book($mockContainer);

        $this->assertInstanceOf(Book::class, $cron);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Book($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $cron);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Book($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $cron);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Book($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Book($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $cron);
    }

    public function testRunFetchesBooks()
    {
        $mockBookModel = $this->createMock(BookModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookModel', $mockBookModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchBooks',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $book->expects($this->once())
            ->method('fetchBooks')
            ->with($mockBookModel)
            ->willReturn([]);
        $book->expects($this->never())
            ->method('getDescription');
        $book->expects($this->never())
            ->method('getDescriptionHtml');
        $book->expects($this->never())
            ->method('getEvent');
        $book->expects($this->never())
            ->method('insertEvent');

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunLogsThrownExceptionsFromFetchBooks()
    {
        $mockExceptionMessage = 'Failed to fetch books';
        $mockException = new Exception($mockExceptionMessage);

        $mockBookModel = $this->createMock(BookModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookModel', $mockBookModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchBooks',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $book->method('fetchBooks')
            ->will($this->throwException($mockException));
        $book->expects($this->never())
            ->method('getDescription');
        $book->expects($this->never())
            ->method('getDescriptionHtml');
        $book->expects($this->never())
            ->method('getEvent');
        $book->expects($this->never())
            ->method('insertEvent');

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunGetEventForEachBook()
    {
        $books = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookModel', $mockBookModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchBooks',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->expects($this->never())
            ->method('getDescription');
        $book->expects($this->never())
            ->method('getDescriptionHtml');
        $book->expects($this->exactly(count($books)))
            ->method('getEvent')
            ->withConsecutive(
                [ $mockEventModel, 'book', $books[0]['id'] ],
                [ $mockEventModel, 'book', $books[1]['id'] ]
            )
            ->willReturn(true);
        $book->expects($this->never())
            ->method('insertEvent');

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunFormatsDescription()
    {
        $bookMetadata = (object) [
            'title' => 'some title',
        ];

        $books = [
            [
                'id' => 1,
                'metadata' => json_encode($bookMetadata),
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookModel', $mockBookModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchBooks',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->expects($this->once())
            ->method('getDescription')
            ->with(
                $this->equalTo($bookMetadata)
            );
        $book->method('getEvent')
            ->willReturn(false);

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunFormatsDescriptionHtml()
    {
        $bookMetadata = (object) [
            'title' => 'some title',
        ];

        $books = [
            [
                'id' => 1,
                'metadata' => json_encode($bookMetadata),
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookModel', $mockBookModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchBooks',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->expects($this->once())
            ->method('getDescriptionHtml')
            ->with(
                $this->equalTo($bookMetadata)
            );
        $book->method('getEvent')
            ->willReturn(false);

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunPassesParamsToInsertEvent()
    {
        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
        $datetime = '2016-06-30 12:00:00';
        $bookId = 1;

        $books = [
            [
                'id' => $bookId,
                'metadata' => '{}',
                'datetime' => $datetime,
            ],
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookModel', $mockBookModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchBooks',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->method('getDescription')
            ->willReturn($description);
        $book->method('getDescriptionHtml')
            ->willReturn($descriptionHtml);
        $book->method('getEvent')
            ->willReturn(false);
        $book->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->equalTo($mockEventModel),
                $this->equalTo($mockTypeModel),
                $this->equalTo($mockUserModel),
                $this->equalTo($description),
                $this->equalTo($descriptionHtml),
                $this->callback(function ($datetimeParam) use ($datetime) {
                    return $datetimeParam->format('Y-m-d H:i:s') === $datetime;
                }),
                $this->callback(function ($metadata) {
                    return $metadata == new stdclass;
                }),
                $this->equalTo('Jacob Emerick'),
                $this->equalTo('book'),
                $this->equalTo($bookId)
            );

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunLogsThrownExceptionFromInsertEvent()
    {
        $mockExceptionMessage = 'Failed to insert book';
        $mockException = new Exception($mockExceptionMessage);

        $books = [
            [
                'id' => 1,
                'metadata' => '{}',
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];
 
        $mockBookModel = $this->createMock(BookModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookModel', $mockBookModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchBooks',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->method('getEvent')
            ->willReturn(false);
        $book->method('insertEvent')
            ->will($this->throwException($mockException));

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunLogsInsertedEventIfSuccessful()
    {
        $books = [
            [
                'id' => 1,
                'metadata' => '{}',
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];
 
        $mockBookModel = $this->createMock(BookModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookModel', $mockBookModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Added book event: 1'));
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchBooks',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->method('getEvent')
            ->willReturn(false);

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testFetchBooksPullsFromModel()
    {
        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->expects($this->once())
            ->method('getBooks');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedFetchBooksMethod = $reflectedBook->getMethod('fetchBooks');
        $reflectedFetchBooksMethod->setAccessible(true);

        $reflectedFetchBooksMethod->invokeArgs($book, [
            $mockBookModel,
        ]);
    }

    public function testFetchBooksReturnsItems()
    {
        $books = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->method('getBooks')
            ->willReturn($books);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedFetchBooksMethod = $reflectedBook->getMethod('fetchBooks');
        $reflectedFetchBooksMethod->setAccessible(true);

        $result = $reflectedFetchBooksMethod->invokeArgs($book, [
            $mockBookModel,
        ]);

        $this->assertEquals($books, $result);
    }

    public function testGetDescriptionFormatsDescription()
    {
        $metadata = (object) [
            'title' => 'some book',
            'author_name' => 'some author',
        ];

        $expectedDescription = 'Read some book by some author.';

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedGetDescriptionMethod = $reflectedBook->getMethod('getDescription');
        $reflectedGetDescriptionMethod->setAccessible(true);

        $result = $reflectedGetDescriptionMethod->invokeArgs($book, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetDescriptionHtmlFormatsDescriptionWithImage()
    {
        $metadata = (object) [
            'book_large_image_url' => 'http://domain.com/some-image.jpg',
            'title' => 'some book',
            'author_name' => 'some author',
        ];

        $expectedDescription = '';
        $expectedDescription .= '<img src="https://domain.com/some-image.jpg" alt="Goodreads | some book" />';
        $expectedDescription .= '<p>Read some book by some author.</p>';

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedGetDescriptionHtmlMethod = $reflectedBook->getMethod('getDescriptionHtml');
        $reflectedGetDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetDescriptionHtmlMethod->invokeArgs($book, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetDescriptionHtmlFormatsDescriptionWithoutImage()
    {
        $metadata = (object) [
            'title' => 'some book',
            'author_name' => 'some author',
        ];

        $expectedDescription = '';
        $expectedDescription .= '<p>Read some book by some author.</p>';

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedGetDescriptionHtmlMethod = $reflectedBook->getMethod('getDescriptionHtml');
        $reflectedGetDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetDescriptionHtmlMethod->invokeArgs($book, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }
}
