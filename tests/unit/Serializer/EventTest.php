<?php

namespace Jacobemerick\LifestreamService\Serializer;

use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{

    public function testIsInstanceOfEvent()
    {
        $serializer = new Event();

        $this->assertInstanceOf(Event::class, $serializer);
    }

    public function testSerializesArray()
    {
        $event = [
            'id' => 123,
            'description' => 'some description',
            'description_html' => '<p>some description</p>',
            'metadata' => '{"name": "value"}',
            'date' => '2016-03-12 14:36:48',
            'user_name' => 'some user',
            'type_name' => 'some type',
        ];

        $serializer = new Event();
        $result = $serializer($event);

        $this->assertSame([
            'id' => 123,
            'description' => 'some description',
            'description_html' => '<p>some description</p>',
            'metadata' => [
                'name' => 'value',
            ],
            'date' => '2016-03-12T14:36:48-07:00',
            'user' => 'some user',
            'type' => 'some type',
        ], $result);
    }
}
