<?php

namespace Jacobemerick\LifestreamService\Cron\Fetch;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;

use PHPUnit\Framework\TestCase;

use Github\Client as Client;
use Github\ResultPager as Pager;
use Github\Api\User as UserApi;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
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
        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('api')
            ->with(
                $this->equalTo('user')
            )
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->expects($this->once())
            ->method('fetch')
            ->with(
                $this->equalTo($mockUserApi),
                $this->equalTo('publicEvents'),
                $this->equalTo([ $mockConfig->code->username ])
            )
            ->willReturn([]);
        $mockPager->method('hasNext')
            ->willReturn(false);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with('Processing page 1 of api results');
        $mockLogger->expects($this->never())
            ->method('error');

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }
 
    public function testRunBailsIfRequestFails()
    {
        $mockExceptionMessage = 'Failed to fetch events';
        $mockException = new Exception($mockExceptionMessage);

        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('api')
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->method('fetch')
            ->will($this->throwException($mockException));
        $mockPager->expects($this->never())
            ->method('hasNext');

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();
        $code->expects($this->never())
            ->method('processEvents');

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunProcessesEvents()
    {
        $events = [
            'some event',
        ];

        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('api')
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->method('fetch')
            ->willReturn($events);
        $mockPager->method('hasNext')
            ->willReturn(false);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();
        $code->expects($this->once())
            ->method('processEvents')
            ->with(
                $this->equalTo($events)
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


    public function testRunBailsIfProcessEventsFails()
    {
        $mockExceptionMessage = 'Failed to process events';
        $mockException = new Exception($mockExceptionMessage);

        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('api')
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->method('fetch')
            ->willReturn([]);
        $mockPager->expects($this->never())
            ->method('hasNext');

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with('Processing page 1 of api results');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();
        $code->method('processEvents')
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

    public function testRunAvoidsSecondHitIfNoPagination()
    {
        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('api')
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->expects($this->once())
            ->method('fetch')
            ->willReturn([]);
        $mockPager->method('hasNext')
            ->willReturn(false);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();
        $code->expects($this->once())
            ->method('processEvents');

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunFetchesAgainIfPagination()
    {
        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('api')
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->method('fetch')
            ->willReturn([]);
        $mockPager->expects($this->once())
            ->method('fetchNext')
            ->willReturn([]);
        $mockPager->method('hasNext')
            ->will($this->onConsecutiveCalls(true, false));

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [ $this->anything() ],
                [ $this->equalTo('Processing page 2 of api results') ]
            );
        $mockLogger->expects($this->never())
            ->method('error');

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunBailsIfSecondRequestFails()
    {
        $mockExceptionMessage = 'Failed to fetch events';
        $mockException = new Exception($mockExceptionMessage);

        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('api')
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->method('fetch')
            ->willReturn([]);
        $mockPager->method('fetchNext')
            ->will($this->throwException($mockException));
        $mockPager->expects($this->once())
            ->method('hasNext')
            ->willReturn(true);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();
        $code->expects($this->once())
            ->method('processEvents');

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testRunProcessesPaginatedEvents()
    {
        $events = [
            'some event',
        ];

        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('api')
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->method('fetch')
            ->willReturn([]);
        $mockPager->method('fetchNext')
            ->willReturn($events);
        $mockPager->method('hasNext')
            ->will($this->onConsecutiveCalls(true, false));

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();
        $code->expects($this->exactly(2))
            ->method('processEvents')
            ->withConsecutive(
                [ $this->anything() ],
                [ $this->equalTo($events) ]
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


    public function testRunBailsIfProcessPaginatedEventsFails()
    {
        $mockExceptionMessage = 'Failed to process events';
        $mockException = new Exception($mockExceptionMessage);

        $mockConfig = (object) [
            'code' => (object) [
                'username' => 'user',
            ],
        ];

        $mockUserApi = $this->createMock(UserApi::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('api')
            ->willReturn($mockUserApi);

        $mockPager = $this->createMock(Pager::class);
        $mockPager->method('fetch')
            ->willReturn([]);
        $mockPager->method('fetchNext')
            ->willReturn([]);
        $mockPager->expects($this->once())
            ->method('hasNext')
            ->willReturn(true);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'codeClient', $mockClient ],
                [ 'codeClientPager', $mockPager ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [ $this->anything() ],
                [ $this->equalTo('Processing page 2 of api results') ]
            );
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'processEvents',
            ])
            ->getMock();
        $code->method('processEvents')
            ->will($this->onConsecutiveCalls(
                null,
                $this->throwException($mockException)
            ));

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $code->run();
    }

    public function testProcessEventsChecksEventExists()
    {
        $events = [[
            'id' => '123',
        ]];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'timezone', $mockDateTimeZone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
 
        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEventExists',
                'insertEvent',
            ])
            ->getMock();
        $code->expects($this->once())
            ->method('checkEventExists')
            ->with(
                $this->equalTo($mockCodeModel),
                $this->equalTo($events[0]['id'])
            );

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $reflectedProcessEventsMethod = $reflectedCode->getMethod('processEvents');
        $reflectedProcessEventsMethod->setAccessible(true);
        $reflectedProcessEventsMethod->invokeArgs($code, [
            $events,
        ]);
    }

    public function testProcessEventsBailsIfExists()
    {
        $events = [[
            'id' => '123',
        ]];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'timezone', $mockDateTimeZone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
 
        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEventExists',
                'insertEvent',
            ])
            ->getMock();
        $code->method('checkEventExists')
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

        $reflectedProcessEventsMethod = $reflectedCode->getMethod('processEvents');
        $reflectedProcessEventsMethod->setAccessible(true);
        $reflectedProcessEventsMethod->invokeArgs($code, [
            $events,
        ]);
    }

    public function testProcessEventsInsertsEventIfNotExists()
    {
        $events = [[
            'id' => '123',
        ]];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'timezone', $mockDateTimeZone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
 
        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEventExists',
                'insertEvent',
            ])
            ->getMock();
        $code->method('checkEventExists')
            ->willReturn(false);
        $code->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->equalTo($mockCodeModel),
                $events[0],
                $this->equalTo($mockDateTimeZone)
            );

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $reflectedProcessEventsMethod = $reflectedCode->getMethod('processEvents');
        $reflectedProcessEventsMethod->setAccessible(true);
        $reflectedProcessEventsMethod->invokeArgs($code, [
            $events,
        ]);
    }

    public function testProcessEventsLogsSuccessfulInserts()
    {
        $events = [[
            'id' => '123',
        ]];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'timezone', $mockDateTimeZone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Inserted new event: 123'));
 
        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEventExists',
                'insertEvent',
            ])
            ->getMock();
        $code->method('checkEventExists')
            ->willReturn(false);

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $reflectedProcessEventsMethod = $reflectedCode->getMethod('processEvents');
        $reflectedProcessEventsMethod->setAccessible(true);
        $reflectedProcessEventsMethod->invokeArgs($code, [
            $events,
        ]);
    }

    public function testProcessEventsHandlesArray()
    {
        $events = [
            [
                'id' => '123',
            ],
            [
                'id' => '456',
            ],
        ];

        $mockCodeModel = $this->createMock(CodeModel::class);
        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'codeModel', $mockCodeModel ],
                [ 'timezone', $mockDateTimeZone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Inserted new event: 456'));
 
        $code = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkEventExists',
                'insertEvent',
            ])
            ->getMock();
        $code->expects($this->exactly(2))
            ->method('checkEventExists')
            ->withConsecutive(
                [ $this->anything(), $events[0]['id'] ],
                [ $this->anything(), $events[1]['id'] ]
            )
            ->will($this->onConsecutiveCalls(true, false));
        $code->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->anything(),
                $events[1],
                $this->anything()
            );

        $reflectedCode = new ReflectionClass(Code::class);

        $reflectedContainerProperty = $reflectedCode->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($code, $mockContainer);

        $reflectedLoggerProperty = $reflectedCode->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($code, $mockLogger);

        $reflectedProcessEventsMethod = $reflectedCode->getMethod('processEvents');
        $reflectedProcessEventsMethod->setAccessible(true);
        $reflectedProcessEventsMethod->invokeArgs($code, [
            $events,
        ]);
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
