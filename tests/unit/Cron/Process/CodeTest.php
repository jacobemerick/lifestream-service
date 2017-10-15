<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use ReflectionClass;
use stdclass;

use PHPUnit\Framework\TestCase;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Code as CodeModel;
use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class CodeTest extends TestCase
{

    public function testIsInstanceOfCode()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Code($mockContainer);

        $this->assertInstanceOf(Code::class, $cron);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Code($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $cron);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Code($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $cron);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Code($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Code($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $cron);
    }

    public function testRunFetchesCodeEvents()
    {
        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchCodeEvents',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $code->expects($this->once())
            ->method('fetchCodeEvents')
            ->with($mockCodeModel)
            ->willReturn([]);
        $code->expects($this->never())
            ->method('getDescriptions');
        $code->expects($this->never())
            ->method('getEvent');
        $code->expects($this->never())
            ->method('insertEvent');

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunLogsThrownExceptionsFromFetchEvents()
    {
        $mockExceptionMessage = 'Failed to fetch events';
        $mockException = new Exception($mockExceptionMessage);

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
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

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchCodeEvents',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $code->method('fetchCodeEvents')
            ->will($this->throwException($mockException));
        $code->expects($this->never())
            ->method('getDescriptions');
        $code->expects($this->never())
            ->method('getEvent');
        $code->expects($this->never())
            ->method('insertEvent');

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunGetEventForEachEvent()
    {
        $events = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchCodeEvents',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $code->method('fetchCodeEvents')
            ->willReturn($events);
        $code->expects($this->never())
            ->method('getDescriptions');
        $code->expects($this->exactly(count($events)))
            ->method('getEvent')
            ->withConsecutive(
                [ $mockEventModel, 'code', $events[0]['id'] ],
                [ $mockEventModel, 'code', $events[1]['id'] ]
            )
            ->willReturn(true);
        $code->expects($this->never())
            ->method('insertEvent');

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunGetsDescriptions()
    {
        $eventMetadata = (object) [
            'title' => 'some title',
        ];
        $eventType = 'some type';

        $events = [
            [
                'id' => 1,
                'metadata' => json_encode($eventMetadata),
                'type' => $eventType,
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchCodeEvents',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $code->method('fetchCodeEvents')
            ->willReturn($events);
        $code->expects($this->once())
            ->method('getDescriptions')
            ->with(
                $this->equalTo($eventType),
                $this->equalTo($eventMetadata)
            );
        $code->method('getEvent')
            ->willReturn(false);

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunPassesParamsToInsertEvent()
    {
        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
        $datetime = '2016-06-30 12:00:00';
        $eventId = 1;

        $events = [
            [
                'id' => $eventId,
                'metadata' => '{}',
                'type' => 'some type',
                'datetime' => $datetime,
            ],
        ];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchCodeEvents',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $code->method('fetchCodeEvents')
            ->willReturn($events);
        $code->method('getDescriptions')
            ->willReturn([ $description, $descriptionHtml ]);
        $code->method('getEvent')
            ->willReturn(false);
        $code->expects($this->once())
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
                $this->equalTo('code'),
                $this->equalTo($eventId)
            );

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunLogsThrownExceptionFromInsertEvent()
    {
        $mockExceptionMessage = 'Failed to insert event';
        $mockException = new Exception($mockExceptionMessage);

        $events = [
            [
                'id' => 1,
                'metadata' => '{}',
                'type' => 'some type',
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];
 
        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
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

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchCodeEvents',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $code->method('fetchCodeEvents')
            ->willReturn($events);
        $code->method('getEvent')
            ->willReturn(false);
        $code->method('insertEvent')
            ->will($this->throwException($mockException));

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunLogsInsertedEventIfSuccessful()
    {
        $events = [
            [
                'id' => 1,
                'metadata' => '{}',
                'type' => 'some type',
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];
 
        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Added code event: 1'));
        $mockLogger->expects($this->never())
            ->method('error');

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchCodeEvents',
                'getDescriptions',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $code->method('fetchCodeEvents')
            ->willReturn($events);
        $code->method('getEvent')
            ->willReturn(false);

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testFetchCodeEventsPullsFromModel()
    {
        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->expects($this->once())
            ->method('getEvents');

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedFetchCodeEventsMethod = $reflectedCode->getMethod('fetchCodeEvents');
        $reflectedFetchCodeEventsMethod->setAccessible(true);

        $reflectedFetchCodeEventsMethod->invokeArgs($code, [
            $mockCodeModel,
        ]);
    }

    public function testFetchCodeEventsReturnsItems()
    {
        $events = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->method('getEvents')
            ->willReturn($events);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedFetchCodeEventsMethod = $reflectedCode->getMethod('fetchCodeEvents');
        $reflectedFetchCodeEventsMethod->setAccessible(true);

        $result = $reflectedFetchCodeEventsMethod->invokeArgs($code, [
            $mockCodeModel,
        ]);

        $this->assertEquals($events, $result);
    }

    public function testGetCreateDescriptionFormatsDescription()
    {
        $metadata = (object) [
            'payload' => (object) [
                'ref_type' => 'some type',
                'ref' => 'some ref',
            ],
            'repo' => (object) [
                'name' => 'some name',
            ],
        ];

        $expectedDescription = 'Created some type some ref at some name.';

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedGetCreateDescriptionMethod = $reflectedCode->getMethod('getCreateDescription');
        $reflectedGetCreateDescriptionMethod->setAccessible(true);

        $result = $reflectedGetCreateDescriptionMethod->invokeArgs($code, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetCreateDescriptionHtmlFormatsCreateDescription()
    {
        $metadata = (object) [
            'payload' => (object) [
                'ref_type' => 'some type',
                'ref' => 'some ref',
            ],
            'repo' => (object) [
                'name' => 'some name',
            ],
        ];

        $expectedDescription = '';
        $expectedDescription .= '<p>Created some type some ref at ';
        $expectedDescription .= '<a href="https://github.com/some name" target="_blank" title="Github | some name">';
        $expectedDescription .= 'some name</a>.</p>';

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedGetCreateDescriptionHtmlMethod = $reflectedCode->getMethod('getCreateDescriptionHtml');
        $reflectedGetCreateDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetCreateDescriptionHtmlMethod->invokeArgs($code, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }
}
