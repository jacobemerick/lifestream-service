<?php

namespace Jacobemerick\LifestreamService\Controller;

use PHPUnit_Framework_TestCase;

class TypeTest extends PHPUnit_Framework_TestCase
{

    public function testIsInstanceOfType()
    {
        $controller = new Type;

        $this->assertInstanceOf(Type::class, $controller);
    }
}
