<?php

namespace Jacobemerick\LifestreamService\Controller;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Serializer\Type as TypeSerializer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface as Stream;

class TypeTest extends TestCase
{

    public function testIsInstanceOfType()
    {
        $mockContainer = $this->createMock(Container::class);
        $controller = new Type($mockContainer);

        $this->assertInstanceOf(Type::class, $controller);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $controller = new Type($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $controller);
    }

    public function testGetTypesPullsFromModel()
    {
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockTypeModel->expects($this->once())
            ->method('getTypes')
            ->willReturn([]);

        $mockTypeSerializer = $this->createMock(TypeSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'typeModel', $mockTypeModel ],
                [ 'typeSerializer', $mockTypeSerializer ],
            ]));

        $mockRequest = $this->createMock(Request::class);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $type = new Type($mockContainer);
        $type->getTypes($mockRequest, $mockResponse);
    }

    public function testGetTypesMapsArrayThroughSerializer()
    {
        $mockTypes = [
            [ 'one' ],
            [ 'two' ],
        ];

        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockTypeModel->method('getTypes')
            ->willReturn($mockTypes);

        $mockTypeSerializer = $this->createMock(TypeSerializer::class);
        $mockTypeSerializer->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [ $this->equalTo($mockTypes[0]) ],
                [ $this->equalTo($mockTypes[1]) ]
            );

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'typeModel', $mockTypeModel ],
                [ 'typeSerializer', $mockTypeSerializer ],
            ]));

        $mockRequest = $this->createMock(Request::class);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $type = new Type($mockContainer);
        $type->getTypes($mockRequest, $mockResponse);
    }

    public function testGetTypesWritesToResponse()
    {
        $mockTypes = [
            [ 'one' ],
            [ 'two' ],
        ];

        $encodedMockTypes = json_encode($mockTypes);

        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockTypeModel->method('getTypes')
            ->willReturn($mockTypes);

        $mockTypeSerializer = $this->createMock(TypeSerializer::class);
        $mockTypeSerializer->method('__invoke')
            ->will($this->returnArgument(0));

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'typeModel', $mockTypeModel ],
                [ 'typeSerializer', $mockTypeSerializer ],
            ]));

        $mockRequest = $this->createMock(Request::class);

        $mockStream = $this->createMock(Stream::class);
        $mockStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo($encodedMockTypes));

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $type = new Type($mockContainer);
        $type->getTypes($mockRequest, $mockResponse);
    }

    public function testGetTypesReturnsResponse()
    {
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockTypeModel->method('getTypes')
            ->willReturn([]);

        $mockTypeSerializer = $this->createMock(TypeSerializer::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'typeModel', $mockTypeModel ],
                [ 'typeSerializer', $mockTypeSerializer ],
            ]));

        $mockRequest = $this->createMock(Request::class);

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn($this->createMock(Stream::class));

        $type = new Type($mockContainer);
        $result = $type->getTypes($mockRequest, $mockResponse);

        $this->assertSame($mockResponse, $result);
    }
}
