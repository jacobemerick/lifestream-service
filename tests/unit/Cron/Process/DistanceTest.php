<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use ReflectionClass;
use stdclass;

use PHPUnit\Framework\TestCase;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Distance as DistanceModel;
use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
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

    public function testRunFetchesEntries()
    {
        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'distanceModel', $mockDistanceModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchEntries',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $distance->expects($this->once())
            ->method('fetchEntries')
            ->with($mockDistanceModel)
            ->willReturn([]);
        $distance->expects($this->never())
            ->method('getDescriptions');
        $distance->expects($this->never())
            ->method('getEvent');
        $distance->expects($this->never())
            ->method('insertEvent');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunLogsThrownExceptionsFromFetchEntries()
    {
        $mockExceptionMessage = 'Failed to fetch entries';
        $mockException = new Exception($mockExceptionMessage);

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'distanceModel', $mockDistanceModel ],
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

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchEntries',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $distance->method('fetchEntries')
            ->will($this->throwException($mockException));
        $distance->expects($this->never())
            ->method('getDescriptions');
        $distance->expects($this->never())
            ->method('getEvent');
        $distance->expects($this->never())
            ->method('insertEvent');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunGetEventForEachEvent()
    {
        $entries = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'distanceModel', $mockDistanceModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchEntries',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->expects($this->never())
            ->method('getDescriptions');
        $distance->expects($this->exactly(count($entries)))
            ->method('getEvent')
            ->withConsecutive(
                [ $mockEventModel, 'distance', $entries[0]['id'] ],
                [ $mockEventModel, 'distance', $entries[1]['id'] ]
            )
            ->willReturn(true);
        $distance->expects($this->never())
            ->method('insertEvent');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunGetsDescriptions()
    {
        $entryMetadata = (object) [
            'title' => 'some title',
        ];
        $entryType = 'some type';

        $entries = [
            [
                'id' => 1,
                'metadata' => json_encode($entryMetadata),
                'type' => $entryType,
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'distanceModel', $mockDistanceModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchEntries',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->expects($this->once())
            ->method('getDescriptions')
            ->with(
                $this->equalTo($entryType),
                $this->equalTo($entryMetadata)
            );
        $distance->method('getEvent')
            ->willReturn(false);

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunLogsThrownExceptionFromGetDescriptions()
    {
        $mockExceptionMessage = 'Failed to get descriptions';
        $mockException = new Exception($mockExceptionMessage);

        $entries = [
            [
                'id' => 1,
                'metadata' => '{}',
                'type' => '',
                'datetime' => '',
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'distanceModel', $mockDistanceModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo($mockExceptionMessage));
        $mockLogger->expects($this->never())
            ->method('error');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchEntries',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->method('getDescriptions')
            ->will($this->throwException($mockException));
        $distance->method('getEvent')
            ->willReturn(false);
        $distance->expects($this->never())
            ->method('insertEvent');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testRunPassesParamsToInsertEvent()
    {
        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
        $datetime = '2016-06-30 12:00:00';
        $entryId = 1;

        $entries = [
            [
                'id' => $entryId,
                'metadata' => '{}',
                'type' => 'some type',
                'datetime' => $datetime,
            ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'distanceModel', $mockDistanceModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchEntries',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->method('getDescriptions')
            ->willReturn([ $description, $descriptionHtml ]);
        $distance->method('getEvent')
            ->willReturn(false);
        $distance->expects($this->once())
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
                $this->equalTo('distance'),
                $this->equalTo($entryId)
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

    public function testRunLogsThrownExceptionFromInsertEvent()
    {
        $mockExceptionMessage = 'Failed to insert entry';
        $mockException = new Exception($mockExceptionMessage);

        $entries = [
            [
                'id' => 1,
                'metadata' => '{}',
                'type' => 'some type',
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];
 
        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'distanceModel', $mockDistanceModel ],
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

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchEntries',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->method('getEvent')
            ->willReturn(false);
        $distance->method('insertEvent')
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

    public function testRunLogsInsertedEventIfSuccessful()
    {
        $entries = [
            [
                'id' => 1,
                'metadata' => '{}',
                'type' => 'some type',
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];
 
        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'distanceModel', $mockDistanceModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Added distance event: 1'));
        $mockLogger->expects($this->never())
            ->method('error');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchEntries',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $distance->method('fetchEntries')
            ->willReturn($entries);
        $distance->method('getEvent')
            ->willReturn(false);

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedContainerProperty = $reflectedDistance->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($distance, $mockContainer);

        $reflectedLoggerProperty = $reflectedDistance->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($distance, $mockLogger);

        $distance->run();
    }

    public function testFetchEntriesPullsFromModel()
    {
        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->expects($this->once())
            ->method('getEntries');

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedFetchEntriesMethod = $reflectedDistance->getMethod('fetchEntries');
        $reflectedFetchEntriesMethod->setAccessible(true);

        $reflectedFetchEntriesMethod->invokeArgs($distance, [
            $mockDistanceModel,
        ]);
    }

    public function testFetchEntriesReturnsItems()
    {
        $entries = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockDistanceModel = $this->createMock(DistanceModel::class);
        $mockDistanceModel->method('getEntries')
            ->willReturn($entries);

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedFetchEntriesMethod = $reflectedDistance->getMethod('fetchEntries');
        $reflectedFetchEntriesMethod->setAccessible(true);

        $result = $reflectedFetchEntriesMethod->invokeArgs($distance, [
            $mockDistanceModel,
        ]);

        $this->assertEquals($entries, $result);
    }

    public function testGetDescriptionsHandlesHikingEntries()
    {
        $type = 'Hiking';
        $metadata = new stdclass;

        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
 
        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getHikingDescription',
                'getHikingDescriptionHtml',
                'getRunningDescription',
                'getRunningDescriptionHtml',
                'getWalkingDescription',
                'getWalkingDescriptionHtml',
            ])
            ->getMock();
        $distance->expects($this->once())
            ->method('getHikingDescription')
            ->with($metadata)
            ->willReturn($description);
        $distance->expects($this->once())
            ->method('getHikingDescriptionHtml')
            ->with($metadata)
            ->willReturn($descriptionHtml);
        $distance->expects($this->never())
            ->method('getRunningDescription');
        $distance->expects($this->never())
            ->method('getRunningDescriptionHtml');
        $distance->expects($this->never())
            ->method('getWalkingDescription');
        $distance->expects($this->never())
            ->method('getWalkingDescriptionHtml');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetDescriptionsMethod = $reflectedDistance->getMethod('getDescriptions');
        $reflectedGetDescriptionsMethod->setAccessible(true);

        $result = $reflectedGetDescriptionsMethod->invokeArgs($distance, [
            $type,
            $metadata,
        ]);

        $this->assertEquals([ $description, $descriptionHtml ], $result);
    }

    public function testGetDescriptionsHandlesRunningEntries()
    {
        $type = 'Running';
        $metadata = new stdclass;

        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
 
        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getHikingDescription',
                'getHikingDescriptionHtml',
                'getRunningDescription',
                'getRunningDescriptionHtml',
                'getWalkingDescription',
                'getWalkingDescriptionHtml',
            ])
            ->getMock();
        $distance->expects($this->never())
            ->method('getHikingDescription');
        $distance->expects($this->never())
            ->method('getHikingDescriptionHtml');
        $distance->expects($this->once())
            ->method('getRunningDescription')
            ->with($metadata)
            ->willReturn($description);
        $distance->expects($this->once())
            ->method('getRunningDescriptionHtml')
            ->with($metadata)
            ->willReturn($descriptionHtml);
        $distance->expects($this->never())
            ->method('getWalkingDescription');
        $distance->expects($this->never())
            ->method('getWalkingDescriptionHtml');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetDescriptionsMethod = $reflectedDistance->getMethod('getDescriptions');
        $reflectedGetDescriptionsMethod->setAccessible(true);

        $result = $reflectedGetDescriptionsMethod->invokeArgs($distance, [
            $type,
            $metadata,
        ]);

        $this->assertEquals([ $description, $descriptionHtml ], $result);
    }

    public function testGetDescriptionsHandlesWalkingEntries()
    {
        $type = 'Walking';
        $metadata = new stdclass;

        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
 
        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getHikingDescription',
                'getHikingDescriptionHtml',
                'getRunningDescription',
                'getRunningDescriptionHtml',
                'getWalkingDescription',
                'getWalkingDescriptionHtml',
            ])
            ->getMock();
        $distance->expects($this->never())
            ->method('getHikingDescription');
        $distance->expects($this->never())
            ->method('getHikingDescriptionHtml');
        $distance->expects($this->never())
            ->method('getRunningDescription');
        $distance->expects($this->never())
            ->method('getRunningDescriptionHtml');
        $distance->expects($this->once())
            ->method('getWalkingDescription')
            ->with($metadata)
            ->willReturn($description);
        $distance->expects($this->once())
            ->method('getWalkingDescriptionHtml')
            ->with($metadata)
            ->willReturn($descriptionHtml);

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetDescriptionsMethod = $reflectedDistance->getMethod('getDescriptions');
        $reflectedGetDescriptionsMethod->setAccessible(true);

        $result = $reflectedGetDescriptionsMethod->invokeArgs($distance, [
            $type,
            $metadata,
        ]);

        $this->assertEquals([ $description, $descriptionHtml ], $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Skipping an entry type: some type
     */
    public function testGetDescriptionsThrowsExceptionForUnknownEntries()
    {
        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getHikingDescription',
                'getHikingDescriptionHtml',
                'getRunningDescription',
                'getRunningDescriptionHtml',
                'getWalkingDescription',
                'getWalkingDescriptionHtml',
            ])
            ->getMock();
        $distance->expects($this->never())
            ->method('getHikingDescription');
        $distance->expects($this->never())
            ->method('getHikingDescriptionHtml');
        $distance->expects($this->never())
            ->method('getRunningDescription');
        $distance->expects($this->never())
            ->method('getRunningDescriptionHtml');
        $distance->expects($this->never())
            ->method('getWalkingDescription');
        $distance->expects($this->never())
            ->method('getWalkingDescriptionHtml');

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetDescriptionsMethod = $reflectedDistance->getMethod('getDescriptions');
        $reflectedGetDescriptionsMethod->setAccessible(true);

        $reflectedGetDescriptionsMethod->invokeArgs($distance, [
            'some type',
            new stdclass,
        ]);
    }

    public function testGetHikingDescriptionFormatsDescription()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
            ],
        ];

        $expectedDescription = 'Hiked 30.00 feet and felt awesome.';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetHikingDescriptionMethod = $reflectedDistance->getMethod('getHikingDescription');
        $reflectedGetHikingDescriptionMethod->setAccessible(true);

        $result = $reflectedGetHikingDescriptionMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetHikingDescriptionHtmlFormatsHikingDescription()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
            ],
        ];

        $expectedDescription = '<p>Hiked 30.00 feet and felt awesome.</p>';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetHikingDescriptionHtmlMethod = $reflectedDistance->getMethod('getHikingDescriptionHtml');
        $reflectedGetHikingDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetHikingDescriptionHtmlMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetHikingDescriptionHtmlFormatsHikingDescriptionWithTitle()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
                'title' => 'Somewhere in the Mazzies',
            ],
        ];

        $expectedDescription = '';
        $expectedDescription .= '<p>Hiked 30.00 feet and felt awesome.</p>';
        $expectedDescription .= '<p>I was hiking up around the Somewhere in the Mazzies area.</p>';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetHikingDescriptionHtmlMethod = $reflectedDistance->getMethod('getHikingDescriptionHtml');
        $reflectedGetHikingDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetHikingDescriptionHtmlMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetRunningDescriptionFormatsDescription()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
            ],
        ];

        $expectedDescription = 'Ran 30.00 feet and felt awesome.';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetRunningDescriptionMethod = $reflectedDistance->getMethod('getRunningDescription');
        $reflectedGetRunningDescriptionMethod->setAccessible(true);

        $result = $reflectedGetRunningDescriptionMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetRunningDescriptionHtmlFormatsDescription()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
            ],
        ];

        $expectedDescription = '<p>Ran 30.00 feet and felt awesome.</p>';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetRunningDescriptionHtmlMethod = $reflectedDistance->getMethod('getRunningDescriptionHtml');
        $reflectedGetRunningDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetRunningDescriptionHtmlMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetRunningDescriptionHtmlFormatsDescriptionWithMessage()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
            ],
            'message' => 'Probably too far',
        ];

        $expectedDescription = '';
        $expectedDescription .= '<p>Ran 30.00 feet and felt awesome.</p>';
        $expectedDescription .= '<p>Afterwards, I was all like Probably too far.</p>';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetRunningDescriptionHtmlMethod = $reflectedDistance->getMethod('getRunningDescriptionHtml');
        $reflectedGetRunningDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetRunningDescriptionHtmlMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetWalkingDescriptionFormatsDescription()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
            ],
        ];

        $expectedDescription = 'Walked 30.00 feet and felt awesome.';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetWalkingDescriptionMethod = $reflectedDistance->getMethod('getWalkingDescription');
        $reflectedGetWalkingDescriptionMethod->setAccessible(true);

        $result = $reflectedGetWalkingDescriptionMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetWalkingDescriptionHtmlFormatsDescription()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
            ],
        ];

        $expectedDescription = '<p>Walked 30.00 feet and felt awesome.</p>';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetWalkingDescriptionHtmlMethod = $reflectedDistance->getMethod('getWalkingDescriptionHtml');
        $reflectedGetWalkingDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetWalkingDescriptionHtmlMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetWalkingDescriptionHtmlFormatsDescriptionWithMessage()
    {
        $metadata = (object) [
            'workout' => (object) [
                'distance' => (object) [
                    'value' => 30,
                    'units' => 'feet',
                ],
                'felt' => 'awesome',
            ],
            'message' => 'Walking is like hiking, only faster.',
        ];

        $expectedDescription = '';
        $expectedDescription .= '<p>Walked 30.00 feet and felt awesome.</p>';
        $expectedDescription .= '<p>Walking is like hiking, only faster.</p>';

        $distance = $this->getMockBuilder(Distance::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedDistance = new ReflectionClass(Distance::class);

        $reflectedGetWalkingDescriptionHtmlMethod = $reflectedDistance->getMethod('getWalkingDescriptionHtml');
        $reflectedGetWalkingDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetWalkingDescriptionHtmlMethod->invokeArgs($distance, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }
}
