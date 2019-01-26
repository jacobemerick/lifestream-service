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

    public function testGetEventsReturnsResponse()
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
}
