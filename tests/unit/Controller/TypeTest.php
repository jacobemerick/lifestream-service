<?php

namespace Jacobemerick\LifestreamService\Controller;

use Interop\Container\ContainerInterface as Container;
use PHPUnit\Framework\TestCase;

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
}
