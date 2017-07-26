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
use Psr\Log\NullLogger;

class DistanceTest extends TestCase
{

    public function testIsInstanceOfDistance()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertInstanceOf(Distance::class, $cron);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $cron);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $cron);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Distance($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $cron);
    }

    public function testFetchEntriesPullsFromClient()
    {
        $username = 'user';

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
                $this->equalTo("people/{$username}/entries.json")
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
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('people//entries.json')
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
            '',
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
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('people//entries.json')
            )
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
