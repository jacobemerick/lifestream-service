<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;

use PHPUnit\Framework\TestCase;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Distance as DistanceModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class DistanceTest extends TestCase
{

    public function testIsInstanceOfDistance()
    {
        $mockContainer = $this->createMock(Container::class);
        $distance = new Distance($mockContainer);

        $this->assertInstanceOf(Distance::class, $distance);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $distance = new Distance($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $distance);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $distance = new Distance($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $distance);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $distance = new Distance($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $distance);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $distance = new Distance($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $distance);
    }

    public function testRunFetchesEntries()
    {
        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->never())
            ->method('checkEntryExists');
        $distance->expects($this->once())
            ->method('fetchEntries')
            ->with(
                $this->equalTo($mockClient),
                $this->equalTo($mockConfig->distance->username),
                $this->anything()
            )
            ->willReturn([]);
        $distance->expects($this->never())
            ->method('insertEntry');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }
 
    public function testRunLogsThrownExceptionFromFetchEntries()
    {
        $mockExceptionMessage = 'Failed to fetch entries';
        $mockException = new Exception($mockExceptionMessage);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->never())
            ->method('checkEntryExists');
        $distance->method('fetchEntries')
            ->will($this->throwException($mockException));
        $distance->expects($this->never())
            ->method('insertEntry');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunChecksIfEachEntryExists()
    {
        $entries = [
            (object) [
                'id' => '123',
            ],
            (object) [
                'id' => '456',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
                [ 'distanceModel', $mockDistanceModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->exactly(count($entries)))
            ->method('checkEntryExists')
            ->withConsecutive(
                [ $mockDistanceModel, $entries[0]->id ],
                [ $mockDistanceModel, $entries[1]->id ]
            )
            ->willReturn(true);
        $distance->method('fetchEntries')
            ->with($mockClient)
            ->willReturn($entries);
        $distance->expects($this->never())
            ->method('insertEntry');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunPassesOntoInsertIfEntryNotExists()
    {
        $entries = [
            (object) [
                'id' => '123',
            ],
            (object) [
                'id' => '456',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
                [ 'distanceModel', $mockDistanceModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->exactly(count($entries)))
            ->method('checkEntryExists')
            ->withConsecutive(
                [ $mockDistanceModel, $entries[0]->id ],
                [ $mockDistanceModel, $entries[1]->id ]
            )
            ->will($this->onConsecutiveCalls(false, true));
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->expects($this->once())
            ->method('insertEntry')
            ->withConsecutive(
                [ $mockDistanceModel, $entries[0], $mockTimezone ]
            );

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunLogsThrownExceptionFromInsertEntry()
    {
        $mockExceptionMessage = 'Failed to insert entry';
        $mockException = new Exception($mockExceptionMessage);

        $entries = [
            (object) [
                'id' => '123',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
                [ 'distanceModel', $mockDistanceModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->exactly(count($entries)))
            ->method('checkEntryExists')
            ->withConsecutive(
                [ $mockDistanceModel, $entries[0]->id ]
            )
            ->willReturn(false);
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->method('insertEntry')
            ->will($this->throwException($mockException));

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunLogsInsertedEntryIfSuccessful()
    {
        $entries = [
            (object) [
                'id' => '123',
            ],
            (object) [
                'id' => '456',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
                [ 'distanceModel', $mockDistanceModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [ $this->anything() ],
                [ $this->equalTo('Inserted new distance entry: 123') ]
            );
        $mockLogger->expects($this->never())
            ->method('error');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->exactly(count($entries)))
            ->method('checkEntryExists')
            ->withConsecutive(
                [ $mockDistanceModel, $entries[0]->id ],
                [ $mockDistanceModel, $entries[1]->id ]
            )
            ->will($this->onConsecutiveCalls(false, true));
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->expects($this->once())
            ->method('insertEntry')
            ->withConsecutive(
                [ $mockDistanceModel, $entries[0], $mockTimezone ]
            );

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunMakesSingleRequestIfNoEntries()
    {
        $entries = [];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
                [ 'distanceModel', $mockDistanceModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Processing page 1 of api results'));
        $mockLogger->expects($this->never())
            ->method('error');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->never())
            ->method('checkEntryExists');
        $distance->expects($this->once())
            ->method('fetchEntries')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(1)
            )
            ->willReturn($entries);
        $distance->expects($this->never())
            ->method('insertEntry');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunMakesSingleRequestIfNoNewEntries()
    {
        $entries = [
            (object) [
                'id' => '123',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
                [ 'distanceModel', $mockDistanceModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Processing page 1 of api results'));
        $mockLogger->expects($this->never())
            ->method('error');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->once())
            ->method('checkEntryExists')
            ->willReturn(true);
        $distance->expects($this->once())
            ->method('fetchEntries')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(1)
            )
            ->willReturn($entries);
        $distance->expects($this->never())
            ->method('insertEntry');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunMakesSingleRequestIfSomeDuplicateEntries()
    {
        $entries = [
            (object) [
                'id' => '123',
            ],
            (object) [
                'id' => '456',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
                [ 'distanceModel', $mockDistanceModel ],
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

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->exactly(2))
            ->method('checkEntryExists')
            ->will($this->onConsecutiveCalls(false, true));
        $distance->expects($this->once())
            ->method('fetchEntries')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(1)
            )
            ->willReturn($entries);

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunMakesMultipleRequestsIfInitialRequestAllNew()
    {
        $entries = [
            (object) [
                'id' => '123',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'distance' => (object) [
                'username' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'distanceClient', $mockClient ],
                [ 'distanceModel', $mockDistanceModel ],
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

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEntryExists',
                'fetchEntries',
                'insertEntry',
            ])
            ->getMock();
        $distance->expects($this->exactly(2))
            ->method('checkEntryExists')
            ->will($this->onConsecutiveCalls(false, true));
        $distance->expects($this->exactly(2))
            ->method('fetchEntries')
            ->withConsecutive(
                [ $this->anything(), $this->anything(), $this->equalTo(1) ],
                [ $this->anything(), $this->anything(), $this->equalTo(2) ]
            )
            ->willReturn($entries);

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testFetchEntriesPullsFromClient()
    {
        $username = 'user';
        $page = 2;

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn('{"entries":[]}');
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo("people/{$username}/entries.json"),
                $this->equalTo([
                    'query' => [ 'page' => $page ],
                ])
            )
            ->willReturn($mockResponse);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedFetchEntriesMethod = $reflectedDistance->getMethod('fetchEntries');
        $reflectedFetchEntriesMethod->setAccessible(true);

        $reflectedFetchEntriesMethod->invokeArgs($distance, [
            $mockClient,
            $username,
            $page,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to fetch entries: 400
     */
    public function testFetchEntriesThrowsExceptionOnNon200Status()
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->never())
            ->method('getBody');
        $mockResponse->method('getStatusCode')
            ->willReturn(400);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('request')
            ->willReturn($mockResponse);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedFetchEntriesMethod = $reflectedDistance->getMethod('fetchEntries');
        $reflectedFetchEntriesMethod->setAccessible(true);

        $reflectedFetchEntriesMethod->invokeArgs($distance, [
            $mockClient,
            '',
            0,
        ]);
    }

    public function testFetchEntriesReturnsEntries()
    {
        $entries = [
            (object) [
                'id' => 1,
            ],
            (object) [
                'id' => 2,
            ],
        ];
        $jsonEntries = json_encode($entries);

        $json = "{\"entries\":{$jsonEntries}}";

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($json);
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('request')
            ->willReturn($mockResponse);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedFetchEntriesMethod = $reflectedDistance->getMethod('fetchEntries');
        $reflectedFetchEntriesMethod->setAccessible(true);

        $result = $reflectedFetchEntriesMethod->invokeArgs($distance, [
            $mockClient,
            '',
            0,
        ]);

        $this->assertEquals($entries, $result);
    }

    public function testCheckEntryExistsPullsFromDistanceModel()
    {
        $entryId = '123';

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->expects($this->once())
            ->method('getEntryByEntryId')
            ->with(
                $this->equalTo($entryId)
            );

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedCheckEntryExistsMethod = $reflectedDistance->getMethod('checkEntryExists');
        $reflectedCheckEntryExistsMethod->setAccessible(true);

        $reflectedCheckEntryExistsMethod->invokeArgs($distance, [
            $mockDistanceModel,
            $entryId,
        ]);
    }

    public function testCheckEntryExistsReturnsTrueIfRecordExists()
    {
        $entry = [
            'id' => '123',
            'entry_id' => '123',
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->method('getEntryByEntryId')
            ->willReturn($entry);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedCheckEntryExistsMethod = $reflectedDistance->getMethod('checkEntryExists');
        $reflectedCheckEntryExistsMethod->setAccessible(true);

        $result = $reflectedCheckEntryExistsMethod->invokeArgs($distance, [
            $mockDistanceModel,
            '',
        ]);

        $this->assertTrue($result);
    }

    public function testCheckEntryExistsReturnsFalsesIfRecordNotExists()
    {
        $entry = false;

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->method('getEntryByEntryId')
            ->willReturn($entry);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedCheckEntryExistsMethod = $reflectedDistance->getMethod('checkEntryExists');
        $reflectedCheckEntryExistsMethod->setAccessible(true);

        $result = $reflectedCheckEntryExistsMethod->invokeArgs($distance, [
            $mockDistanceModel,
            '',
        ]);

        $this->assertFalse($result);
    }

    public function testInsertEntryCastsDateToDateTime()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $mockEntry = (object) [
            'id' => '123',
            'workout' => (object) [
                'activity_type' => 'something',
            ],
            'at' => $date,
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->expects($this->once())
            ->method('insertEntry')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo($dateTime),
                $this->anything()
            )
            ->willReturn(true);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedInsertEntryMethod = $reflectedDistance->getMethod('insertEntry');
        $reflectedInsertEntryMethod->setAccessible(true);

        $reflectedInsertEntryMethod->invokeArgs($distance, [
            $mockDistanceModel,
            $mockEntry,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertEntrySetsDateTimeZone()
    {
        $date = '2016-06-30 12:00:00 +000';
        $timezone = 'America/Phoenix'; // always +700, no DST

        $mockEntry = (object) [
            'id' => '123',
            'workout' => (object) [
                'activity_type' => 'something',
            ],
            'at' => $date,
        ];

        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime($date);
        $dateTime->setTimezone($dateTimeZone);

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->expects($this->once())
            ->method('insertEntry')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($param) use ($dateTime) {
                    return $param->getTimeZone()->getName() == $dateTime->getTimeZone()->getName();
                }),
                $this->anything()
            )
            ->willReturn(true);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedInsertEntryMethod = $reflectedDistance->getMethod('insertEntry');
        $reflectedInsertEntryMethod->setAccessible(true);

        $reflectedInsertEntryMethod->invokeArgs($distance, [
            $mockDistanceModel,
            $mockEntry,
            $dateTimeZone,
        ]);
    }

    public function testInsertEntrySendsParamsToDistanceModel()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $id = '123';
        $activityType = 'something';

        $mockEntry = (object) [
            'id' => $id,
            'workout' => (object) [
                'activity_type' => $activityType,
            ],
            'at' => $date,
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->expects($this->once())
            ->method('insertEntry')
            ->with(
                $this->equalTo($id),
                $this->equalTo($activityType),
                $this->equalTo($dateTime),
                $this->equalTo(json_encode($mockEntry))
            )
            ->willReturn(true);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedInsertEntryMethod = $reflectedDistance->getMethod('insertEntry');
        $reflectedInsertEntryMethod->setAccessible(true);

        $reflectedInsertEntryMethod->invokeArgs($distance, [
            $mockDistanceModel,
            $mockEntry,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to insert entry
     */
    public function testInsertEntryThrowsExceptionIfModelThrows()
    {
        $exception = new Exception('Failed to insert entry');

        $mockEntry = (object) [
            'id' => '123',
            'workout' => (object) [
                'activity_type' => 'something',
            ],
            'at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->method('insertEntry')
            ->will($this->throwException($exception));

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedInsertEntryMethod = $reflectedDistance->getMethod('insertEntry');
        $reflectedInsertEntryMethod->setAccessible(true);

        $reflectedInsertEntryMethod->invokeArgs($distance, [
            $mockDistanceModel,
            $mockEntry,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to insert new entry: 123
     */
    public function testInsertEntryThrowsExceptionIfInsertFails()
    {
        $id = '123';

        $mockEntry = (object) [
            'id' => $id,
            'workout' => (object) [
                'activity_type' => 'something',
            ],
            'at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->method('insertEntry')
            ->willReturn(false);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedInsertEntryMethod = $reflectedDistance->getMethod('insertEntry');
        $reflectedInsertEntryMethod->setAccessible(true);

        $reflectedInsertEntryMethod->invokeArgs($distance, [
            $mockDistanceModel,
            $mockEntry,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertEntryReturnsTrueIfInsertSucceeds()
    {
        $mockEntry = (object) [
            'id' => '123',
            'workout' => (object) [
                'activity_type' => 'something',
            ],
            'at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->method('insertEntry')
            ->willReturn(true);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);
        $reflectedInsertEntryMethod = $reflectedDistance->getMethod('insertEntry');
        $reflectedInsertEntryMethod->setAccessible(true);

        $result = $reflectedInsertEntryMethod->invokeArgs($distance, [
            $mockDistanceModel,
            $mockEntry,
            $mockDateTimeZone,
        ]);

        $this->assertTrue($result);
    }
}
