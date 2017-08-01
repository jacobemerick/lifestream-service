<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;
use SimpleXMLElement;

use PHPUnit\Framework\TestCase;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Book as BookModel;
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
        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookClient', $mockClient ],
                [ 'config', $mockConfig ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->never())
            ->method('checkBookExists');
        $book->expects($this->once())
            ->method('fetchBooks')
            ->with(
                $this->equalTo($mockClient),
                $this->equalTo($mockConfig->book->shelf),
                $this->anything()
            )
            ->willReturn([]);
        $book->expects($this->never())
            ->method('insertBook');

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

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookClient', $mockClient ],
                [ 'config', $mockConfig ],
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
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->never())
            ->method('checkBookExists');
        $book->method('fetchBooks')
            ->will($this->throwException($mockException));
        $book->expects($this->never())
            ->method('insertBook');

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunSkipsBookIfBookIsUnread()
    {
        $books = [
            new SimpleXMLElement('<rss><user_read_at></user_read_at><book_id>123</book_id></rss>'),
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->exactly(1))
            ->method('debug')
            ->with($this->equalTo('Processing page 1 of api results'));
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->never())
            ->method('checkBookExists');
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->expects($this->never())
            ->method('insertBook');

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunChecksIfEachBookExists()
    {
        $books = [
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>123</book_id></rss>'),
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>456</book_id></rss>'),
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'config', $mockConfig ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->exactly(count($books)))
            ->method('checkBookExists')
            ->withConsecutive(
                [ $mockBookModel, $books[0]->book_id ],
                [ $mockBookModel, $books[1]->book_id ]
            )
            ->willReturn(true);
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->expects($this->never())
            ->method('insertBook');

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunPassesOntoInsertIfBookNotExists()
    {
        $books = [
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>123</book_id></rss>'),
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>456</book_id></rss>'),
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'config', $mockConfig ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->exactly(count($books)))
            ->method('checkBookExists')
            ->withConsecutive(
                [ $mockBookModel, $books[0]->book_id ],
                [ $mockBookModel, $books[1]->book_id ]
            )
            ->will($this->onConsecutiveCalls(false, true));
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->expects($this->once())
            ->method('insertBook')
            ->withConsecutive(
                [ $mockBookModel, $books[0], $mockTimezone ]
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

    public function testRunLogsThrownExceptionFromInsertBook()
    {
        $mockExceptionMessage = 'Failed to insert book';
        $mockException = new Exception($mockExceptionMessage);

        $books = [
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>123</book_id></rss>'),
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'config', $mockConfig ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->exactly(count($books)))
            ->method('checkBookExists')
            ->withConsecutive(
                [ $mockBookModel, $books[0]->book_id ]
            )
            ->willReturn(false);
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->method('insertBook')
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

    public function testRunLogsInsertedBookIfSuccessful()
    {
        $books = [
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>123</book_id></rss>'),
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>456</book_id></rss>'),
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'config', $mockConfig ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [ $this->anything() ],
                [ $this->equalTo('Inserted new book: 123') ]
            );

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->exactly(count($books)))
            ->method('checkBookExists')
            ->withConsecutive(
                [ $mockBookModel, $books[0]->book_id ],
                [ $mockBookModel, $books[1]->book_id ]
            )
            ->will($this->onConsecutiveCalls(false, true));
        $book->method('fetchBooks')
            ->willReturn($books);
        $book->expects($this->once())
            ->method('insertBook')
            ->withConsecutive(
                [ $mockBookModel, $books[0], $mockTimezone ]
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

    public function testRunMakesSingleRequestIfNoBooks()
    {
        $books = [];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Processing page 1 of api results'));
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->never())
            ->method('checkBookExists');
        $book->expects($this->once())
            ->method('fetchBooks')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(1)
            )
            ->willReturn($books);
        $book->expects($this->never())
            ->method('insertBook');

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunMakesSingleRequestIfNoNewBooks()
    {
        $books = [
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>123</book_id></rss>'),
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Processing page 1 of api results'));
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->once())
            ->method('checkBookExists')
            ->willReturn(true);
        $book->expects($this->once())
            ->method('fetchBooks')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(1)
            )
            ->willReturn($books);
        $book->expects($this->never())
            ->method('insertBook');

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunMakesSingleRequestIfSomeDuplicateBooks()
    {
        $books = [
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>123</book_id></rss>'),
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>456</book_id></rss>'),
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [ $this->equalTo('Processing page 1 of api results') ],
                [ $this->anything() ]
            );
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->exactly(2))
            ->method('checkBookExists')
            ->will($this->onConsecutiveCalls(false, true));
        $book->expects($this->once())
            ->method('fetchBooks')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(1)
            )
            ->willReturn($books);

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testRunMakesMultipleRequestsIfInitialRequestAllNew()
    {
        $books = [
            new SimpleXMLElement('<rss><user_read_at>now</user_read_at><book_id>123</book_id></rss>'),
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'book' => (object) [
                'shelf' => 'user_shelf',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'bookClient', $mockClient ],
                [ 'bookModel', $mockBookModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->exactly(3))
            ->method('debug')
            ->withConsecutive(
                [ $this->equalTo('Processing page 1 of api results') ],
                [ $this->anything() ],
                [ $this->equalTo('Processing page 2 of api results') ]
            );
        $mockLogger->expects($this->never())
            ->method('error');

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkBookExists',
                'fetchBooks',
                'insertBook',
            ])
            ->getMock();
        $book->expects($this->exactly(2))
            ->method('checkBookExists')
            ->will($this->onConsecutiveCalls(false, true));
        $book->expects($this->exactly(2))
            ->method('fetchBooks')
            ->withConsecutive(
                [ $this->anything(), $this->anything(), $this->equalTo(1) ],
                [ $this->anything(), $this->anything(), $this->equalTo(2) ]
            )
            ->willReturn($books);

        $reflectedBook = new ReflectionClass(Book::class);

        $reflectedContainerProperty = $reflectedBook->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($book, $mockContainer);

        $reflectedLoggerProperty = $reflectedBook->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($book, $mockLogger);

        $book->run();
    }

    public function testFetchBooksPullsFromClient()
    {
        $shelf = 'user_shelf';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn('<rss><channel><item /></channel></rss>');
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo("/review/list_rss/{$shelf}")
            )
            ->willReturn($mockResponse);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedFetchBooksMethod = $reflectedBook->getMethod('fetchBooks');
        $reflectedFetchBooksMethod->setAccessible(true);

        $reflectedFetchBooksMethod->invokeArgs($book, [
            $mockClient,
            $shelf,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to fetch books: 400
     */
    public function testFetchBooksThrowsExceptionOnNon200Status()
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->never())
            ->method('getBody');
        $mockResponse->method('getStatusCode')
            ->willReturn(400);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/review/list_rss/')
            )
            ->willReturn($mockResponse);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedFetchBooksMethod = $reflectedBook->getMethod('fetchBooks');
        $reflectedFetchBooksMethod->setAccessible(true);

        $reflectedFetchBooksMethod->invokeArgs($book, [
            $mockClient,
            '',
        ]);
    }

    public function testFetchBooksReturnsItems()
    {
        $items = '<item><id>123</id></item>';
        $xmlItems = new SimpleXMLElement($items);

        $rss = "<rss><channel>{$items}</channel></rss>";

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($rss);
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/review/list_rss/')
            )
            ->willReturn($mockResponse);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedFetchBooksMethod = $reflectedBook->getMethod('fetchBooks');
        $reflectedFetchBooksMethod->setAccessible(true);

        $result = $reflectedFetchBooksMethod->invokeArgs($book, [
            $mockClient,
            '',
        ]);

        $this->assertEquals($xmlItems, $result);
    }

    public function testCheckBookExistsPullsFromBookModel()
    {
        $bookId = '123';

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->expects($this->once())
            ->method('getBookByBookId')
            ->with(
                $this->equalTo($bookId)
            );

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedCheckBookExistsMethod = $reflectedBook->getMethod('checkBookExists');
        $reflectedCheckBookExistsMethod->setAccessible(true);

        $reflectedCheckBookExistsMethod->invokeArgs($book, [
            $mockBookModel,
            $bookId,
        ]);
    }

    public function testCheckBookExistsReturnsTrueIfRecordExists()
    {
        $book = [
            'id' => '123',
            'bookId' => '123',
        ];

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->method('getBookByBookId')
            ->willReturn($book);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedCheckBookExistsMethod = $reflectedBook->getMethod('checkBookExists');
        $reflectedCheckBookExistsMethod->setAccessible(true);

        $result = $reflectedCheckBookExistsMethod->invokeArgs($book, [
            $mockBookModel,
            '',
        ]);

        $this->assertTrue($result);
    }

    public function testCheckBookExistsReturnsFalsesIfRecordNotExists()
    {
        $book = false;

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->method('getBookByBookId')
            ->willReturn($book);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedCheckBookExistsMethod = $reflectedBook->getMethod('checkBookExists');
        $reflectedCheckBookExistsMethod->setAccessible(true);

        $result = $reflectedCheckBookExistsMethod->invokeArgs($book, [
            $mockBookModel,
            '',
        ]);

        $this->assertFalse($result);
    }

    public function testInsertBookCastsDateToDateTime()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $mockBook = "<item><book_id /><guid /><user_read_at>{$date}</user_read_at></item>";
        $mockBook = new SimpleXMLElement($mockBook);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->expects($this->once())
            ->method('insertBook')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo($dateTime),
                $this->anything()
            )
            ->willReturn(true);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedInsertBookMethod = $reflectedBook->getMethod('insertBook');
        $reflectedInsertBookMethod->setAccessible(true);

        $reflectedInsertBookMethod->invokeArgs($book, [
            $mockBookModel,
            $mockBook,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertBookSetsDateTimeZone()
    {
        $date = '2016-06-30 12:00:00 +000';
        $timezone = 'America/Phoenix'; // always +700, no DST

        $mockBook = "<item><book_id /><guid /><user_read_at>{$date}</user_read_at></item>";
        $mockBook = new SimpleXMLElement($mockBook);

        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime($date);
        $dateTime->setTimezone($dateTimeZone);

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->expects($this->once())
            ->method('insertBook')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($param) use ($dateTime) {
                    return $param->getTimeZone()->getName() == $dateTime->getTimeZone()->getName();
                }),
                $this->anything()
            )
            ->willReturn(true);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedInsertBookMethod = $reflectedBook->getMethod('insertBook');
        $reflectedInsertBookMethod->setAccessible(true);

        $reflectedInsertBookMethod->invokeArgs($book, [
            $mockBookModel,
            $mockBook,
            $dateTimeZone,
        ]);
    }

    public function testInsertBookSendsParamsToBookModel()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $bookId = '123';
        $guid = 'http://site.com/some-book';

        $mockBook = "<item><book_id>{$bookId}</book_id><guid>{$guid}</guid>";
        $mockBook .= "<user_read_at>{$date}</user_read_at></item>";
        $mockBook = new SimpleXMLElement($mockBook);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->expects($this->once())
            ->method('insertBook')
            ->with(
                $this->equalTo($bookId),
                $this->equalTo($guid),
                $this->equalTo($dateTime),
                $this->equalTo(json_encode($mockBook))
            )
            ->willReturn(true);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedInsertBookMethod = $reflectedBook->getMethod('insertBook');
        $reflectedInsertBookMethod->setAccessible(true);

        $reflectedInsertBookMethod->invokeArgs($book, [
            $mockBookModel,
            $mockBook,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to insert book
     */
    public function testInsertBookThrowsExceptionIfModelThrows()
    {
        $exception = new Exception('Failed to insert book');

        $mockBook = "<item><book_id /><guid /><pubDate>2016-06-30 12:00:00</pubDate></item>";
        $mockBook = new SimpleXMLElement($mockBook);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->method('insertBook')
            ->will($this->throwException($exception));

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedInsertBookMethod = $reflectedBook->getMethod('insertBook');
        $reflectedInsertBookMethod->setAccessible(true);

        $reflectedInsertBookMethod->invokeArgs($book, [
            $mockBookModel,
            $mockBook,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to insert new book: 123
     */
    public function testInsertBookThrowsExceptionIfInsertFails()
    {
        $bookId = '123';

        $mockBook = "<item><book_id>{$bookId}</book_id><guid /><pubDate>2016-06-30 12:00:00</pubDate></item>";
        $mockBook = new SimpleXMLElement($mockBook);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->method('insertBook')
            ->willReturn(false);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedInsertBookMethod = $reflectedBook->getMethod('insertBook');
        $reflectedInsertBookMethod->setAccessible(true);

        $reflectedInsertBookMethod->invokeArgs($book, [
            $mockBookModel,
            $mockBook,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertBookReturnsTrueIfInsertSucceeds()
    {
        $mockBook = "<item><book_id /><guid /><pubDate>2016-06-30 12:00:00</pubDate></item>";
        $mockBook = new SimpleXMLElement($mockBook);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBookModel = $this->createMock(BookModel::class);
        $mockBookModel->method('insertBook')
            ->willReturn(true);

        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBook = new ReflectionClass(Book::class);
        $reflectedInsertBookMethod = $reflectedBook->getMethod('insertBook');
        $reflectedInsertBookMethod->setAccessible(true);

        $result = $reflectedInsertBookMethod->invokeArgs($book, [
            $mockBookModel,
            $mockBook,
            $mockDateTimeZone,
        ]);

        $this->assertTrue($result);
    }
}
