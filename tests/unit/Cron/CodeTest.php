<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;

use PHPUnit\Framework\TestCase;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Code as CodeModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class CodeTest extends TestCase
{

    public function testIsInstanceOfCode()
    {
        $mockContainer = $this->createMock(Container::class);
        $code = new Code($mockContainer);

        $this->assertInstanceOf(Code::class, $code);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $code = new Code($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $code);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $code = new Code($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $code);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $code = new Code($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $code);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $code = new Code($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $code);
    }

    public function testRunFetchesEvents()
    {
        $this->markTestIncomplete();
    }
 
    public function testRunProcessesEvents()
    {
        $this->markTestIncomplete();
    }

    public function testRunAvoidsSecondHitIfNoPagination()
    {
        $this->markTestIncomplete();
    }

    public function testRunFetchesAgainIfPagination()
    {
        $this->markTestIncomplete();
    }

    public function testRunProcessesSecondPageResults()
    {
        $this->markTestIncomplete();
    }

    public function testProcessEventChecksEventExists()
    {
        $this->markTestIncomplete();
    }

    public function testProcessEventIgnoresEventIfExists()
    {
        $this->markTestIncomplete();
    }

    public function testProcessEventInsertsEventIfNotExists()
    {
        $this->markTestIncomplete();
    }

    public function testProcessEventLogsSuccessfulInserts()
    {
        $this->markTestIncomplete();
    }

    public function testCheckEventExistsPullsFromCodeModel()
    {
        $eventId = '123';

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->expects($this->once())
            ->method('getEventByEventId')
            ->with(
                $this->equalTo($eventId)
            );

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedCheckEventExistsMethod = $reflectedCode->getMethod('checkEventExists');
        $reflectedCheckEventExistsMethod->setAccessible(true);

        $reflectedCheckEventExistsMethod->invokeArgs($code, [
            $mockCodeModel,
            $eventId,
        ]);
    }

    public function testCheckEventExistsReturnsTrueIfRecordExists()
    {
        $event = [
            'id' => '123',
            'event_id' => '123',
        ];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->method('getEventByEventId')
            ->willReturn($event);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedCheckEventExistsMethod = $reflectedCode->getMethod('checkEventExists');
        $reflectedCheckEventExistsMethod->setAccessible(true);

        $result = $reflectedCheckEventExistsMethod->invokeArgs($code, [
            $mockCodeModel,
            '',
        ]);

        $this->assertTrue($result);
    }

    public function testCheckEventExistsReturnsFalsesIfRecordNotExists()
    {
        $event = false;

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->method('getEventByEventId')
            ->willReturn($event);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedCheckEventExistsMethod = $reflectedCode->getMethod('checkEventExists');
        $reflectedCheckEventExistsMethod->setAccessible(true);

        $result = $reflectedCheckEventExistsMethod->invokeArgs($code, [
            $mockCodeModel,
            '',
        ]);

        $this->assertFalse($result);
    }

    public function testInsertEventCastsDateToDateTime()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $mockEvent = [
            'id' => '123',
            'type' => 'some type',
            'created_at' => $date,
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo($dateTime),
                $this->anything()
            )
            ->willReturn(true);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedInsertEventMethod = $reflectedCode->getMethod('insertEvent');
        $reflectedInsertEventMethod->setAccessible(true);

        $reflectedInsertEventMethod->invokeArgs($code, [
            $mockCodeModel,
            $mockEvent,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertEventSetsDateTimeZone()
    {
        $date = '2016-06-30 12:00:00 +000';
        $timezone = 'America/Phoenix'; // always +700, no DST

        $mockEvent = [
            'id' => '123',
            'type' => 'some type',
            'created_at' => $date,
        ];

        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime($date);
        $dateTime->setTimezone($dateTimeZone);

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($param) use ($dateTime) {
                    return $param->getTimeZone()->getName() == $dateTime->getTimeZone()->getName();
                }),
                $this->anything()
            )
            ->willReturn(true);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedInsertEventMethod = $reflectedCode->getMethod('insertEvent');
        $reflectedInsertEventMethod->setAccessible(true);

        $reflectedInsertEventMethod->invokeArgs($code, [
            $mockCodeModel,
            $mockEvent,
            $dateTimeZone,
        ]);
    }

    public function testInsertEventSendsParamsToCodeModel()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $id = '123';
        $type = 'some type';

        $mockEvent = [
            'id' => $id,
            'type' => $type,
            'created_at' => $date,
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->equalTo($id),
                $this->equalTo($type),
                $this->equalTo($dateTime),
                $this->equalTo(json_encode($mockEvent))
            )
            ->willReturn(true);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedInsertEventMethod = $reflectedCode->getMethod('insertEvent');
        $reflectedInsertEventMethod->setAccessible(true);

        $reflectedInsertEventMethod->invokeArgs($code, [
            $mockCodeModel,
            $mockEvent,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to insert event
     */
    public function testInsertEventThrowsExceptionIfModelThrows()
    {
        $exception = new Exception('Failed to insert event');

        $mockEvent = [
            'id' => '123',
            'type' => 'some type',
            'created_at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->method('insertEvent')
            ->will($this->throwException($exception));

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedInsertEventMethod = $reflectedCode->getMethod('insertEvent');
        $reflectedInsertEventMethod->setAccessible(true);

        $reflectedInsertEventMethod->invokeArgs($code, [
            $mockCodeModel,
            $mockEvent,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to insert new event: 123
     */
    public function testInsertEventThrowsExceptionIfInsertFails()
    {
        $id = '123';

        $mockEvent = [
            'id' => $id,
            'type' => 'some type',
            'created_at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->method('insertEvent')
            ->willReturn(false);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedInsertEventMethod = $reflectedCode->getMethod('insertEvent');
        $reflectedInsertEventMethod->setAccessible(true);

        $reflectedInsertEventMethod->invokeArgs($code, [
            $mockCodeModel,
            $mockEvent,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertEventReturnsTrueIfInsertSucceeds()
    {
        $mockEvent = [
            'id' => '123',
            'type' => 'some type',
            'created_at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockCodeModel->method('insertEvent')
            ->willReturn(true);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);
        $reflectedInsertEventMethod = $reflectedCode->getMethod('insertEvent');
        $reflectedInsertEventMethod->setAccessible(true);

        $result = $reflectedInsertEventMethod->invokeArgs($code, [
            $mockCodeModel,
            $mockEvent,
            $mockDateTimeZone,
        ]);

        $this->assertTrue($result);
    }
}
