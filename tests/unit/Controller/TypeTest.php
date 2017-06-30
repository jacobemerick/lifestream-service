<?php

namespace Jacobemerick\LifestreamService\Controller;

use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{

    public function testIsInstanceOfType()
    {
        $controller = new Type;

        $this->assertInstanceOf(Type::class, $controller);
    }
}
