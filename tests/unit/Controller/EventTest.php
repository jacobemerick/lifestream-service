<?php

namespace Jacobemerick\LifestreamService\Controller;

use AvalancheDevelopment\SwaggerRouterMiddleware\ParsedSwaggerInterface;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Serializer\Event as EventSerializer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface as Stream;
use ReflectionClass;

class EventTest extends TestCase
{

    public function testIsInstanceOfEvent()
    {
        $mockContainer = $this->createMock(Container::class);
        $controller = new Event($mockContainer);

        $this->assertInstanceOf(Event::class, $controller);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $controller = new Event($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $controller);
    }

    public function testGetEventSendsEventId()
    {
        $eventId = 927;

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->expects($this->once())
            ->method('findById')
            ->with(
                $this->equalTo($eventId)
            )
            ->willReturn([ 'some value' ]);

        $mockEventSerializer = $this->createMock(EventSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'event_id' => [
                    'value' => $eventId,
                ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvent($mockRequest, $mockResponse);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\NotFound
     * @expectedExceptionMessage No event found
     */
    public function testGetEventBailsOnInvalidEvent()
    {
        $eventId = 927;

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->method('findById')
            ->willReturn(false);

        $mockEventSerializer = $this->createMock(EventSerializer::class);
        $mockEventSerializer->expects($this->never())
            ->method('__invoke');

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'event_id' => [
                    'value' => $eventId,
                ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvent($mockRequest, $mockResponse);
    }

    public function testGetEventPassesResultToSerializer()
    {
        $mockEvent = [
            'name' => 'some event',
            'type' => 'puppies',
        ];

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->method('findById')
            ->willReturn($mockEvent);

        $mockEventSerializer = $this->createMock(EventSerializer::class);
        $mockEventSerializer->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->equalTo($mockEvent)
            );

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'event_id' => [
                    'value' => 123,
                ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvent($mockRequest, $mockResponse);
    }

    public function testGetEventWritesToResponse()
    {
        $mockEvent = [
            'name' => 'some event',
            'type' => 'puppies',
        ];

        $encodedMockEvent = json_encode($mockEvent);

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->method('findById')
            ->willReturn($mockEvent);

        $mockEventSerializer = $this->createMock(EventSerializer::class);
        $mockEventSerializer->method('__invoke')
            ->will($this->returnArgument(0));

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'event_id' => [
                    'value' => 123,
                ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockStream = $this->createMock(Stream::class);
        $mockStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo($encodedMockEvent));

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvent($mockRequest, $mockResponse);
    }

    public function testGetEventReturnsResponse()
    {
        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->method('findById')
            ->willReturn([ 'someValue' ]);

        $mockEventSerializer = $this->createMock(EventSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'event_id' => [
                    'value' => 123,
                ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $result = $event->getEvent($mockRequest, $mockResponse);

        $this->assertSame($mockResponse, $result);
    }

    public function testGetEventsSendsDefaultParams()
    {
        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->expects($this->once())
            ->method('getEvents')
            ->with(
                $this->equalTo(0),
                $this->equalTo(0),
                $this->equalTo(''),
                $this->equalTo(''),
                $this->equalTo(''),
                $this->equalTo(true)
            )
            ->willReturn([]);

        $mockEventSerializer = $this->createMock(EventSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getParams')
            ->willReturn([]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvents($mockRequest, $mockResponse);
    }

    public function testGetEventsSendsPagination()
    {
        $page = 2;
        $per_page = 15;

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->expects($this->once())
            ->method('getEvents')
            ->with(
                $this->equalTo($per_page),
                $this->equalTo(($page - 1) * $per_page),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn([]);

        $mockEventSerializer = $this->createMock(EventSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'page' => [ 'value' => $page ],
                'per_page' => [ 'value' => $per_page ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvents($mockRequest, $mockResponse);
    }

    public function testGetEventsSendsFilters()
    {
        $type = 'some type';
        $user = 'some user';

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->expects($this->once())
            ->method('getEvents')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo($type),
                $this->equalTo($user),
                $this->anything(),
                $this->anything()
            )
            ->willReturn([]);

        $mockEventSerializer = $this->createMock(EventSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'user' => [ 'value' => $user ],
                'type' => [ 'value' => $type ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvents($mockRequest, $mockResponse);
    }

    public function testGetEventsSendsNormalOrder()
    {
        $sort = 'date';

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->expects($this->once())
            ->method('getEvents')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo($sort),
                $this->equalTo(true)
            )
            ->willReturn([]);

        $mockEventSerializer = $this->createMock(EventSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'sort' => [ 'value' => $sort ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvents($mockRequest, $mockResponse);
    }

    public function testGetEventsSendsReverseOrder()
    {
        $sort = '-date';

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->expects($this->once())
            ->method('getEvents')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo(substr($sort, 1)),
                $this->equalTo(false)
            )
            ->willReturn([]);

        $mockEventSerializer = $this->createMock(EventSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'sort' => [ 'value' => $sort ],
            ]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvents($mockRequest, $mockResponse);
    }

    public function testGetEventsPassesResultToSerializer()
    {
        $mockEvents = [
            [
                'name' => 'some event',
                'type' => 'puppies',
            ],
            [
                'name' => 'some other event',
                'type' => 'puppies',
            ]
        ];

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->method('getEvents')
            ->willReturn($mockEvents);

        $mockEventSerializer = $this->createMock(EventSerializer::class);
        $mockEventSerializer->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [ $this->equalTo($mockEvents[0]) ],
                [ $this->equalTo($mockEvents[1]) ]
            );

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getParams')
            ->willReturn([]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvents($mockRequest, $mockResponse);
    }

    public function testGetEventsWritesToResponse()
    {
        $mockEvents = [
            [
                'name' => 'some event',
                'type' => 'puppies',
            ],
            [
                'name' => 'some other event',
                'type' => 'puppies',
            ]
        ];

        $encodedMockEvents = json_encode($mockEvents);

        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->method('getEvents')
            ->willReturn($mockEvents);

        $mockEventSerializer = $this->createMock(EventSerializer::class);
        $mockEventSerializer->method('__invoke')
            ->will($this->returnArgument(0));

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getParams')
            ->willReturn([]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockStream = $this->createMock(Stream::class);
        $mockStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo($encodedMockEvents));

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $event->getEvents($mockRequest, $mockResponse);
    }

    public function testGetEventsReturnsResponse()
    {
        $mockEventModel = $this->createMock(EventModel::class);
        $mockEventModel->method('getEvents')
            ->willReturn([]);

        $mockEventSerializer = $this->createMock(EventSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'eventSerializer', $mockEventSerializer ],
            ]));

        $mockSwagger = $this->createMock(ParsedSwaggerInterface::class);
        $mockSwagger->method('getParams')
            ->willReturn([]);

        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn($mockSwagger);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedEvent = new ReflectionClass(Event::class);
        $reflectedContainerProperty = $reflectedEvent->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($event, $mockContainer);

        $result = $event->getEvents($mockRequest, $mockResponse);

        $this->assertSame($mockResponse, $result);
    }
}
