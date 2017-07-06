<?php

namespace Jacobemerick\LifestreamService\Serializer;

use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{

    public function testIsInstanceOfType()
    {
        $serializer = new Type();

        $this->assertInstanceOf(Type::class, $serializer);
    }

    public function testSerializesArray()
    {
        $type = [
            'id' => 1,
            'name' => 'type one',
        ];

        $serializer = new Type();
        $result = $serializer($type);

        $this->assertSame([
            'name' => 'type one',
        ], $result);
    }
}
