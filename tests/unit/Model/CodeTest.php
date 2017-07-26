<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class CodeTest extends TestCase
{

    public function testIsInstanceOfCode()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Code($mockPdo);

        $this->assertInstanceOf(Code::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Code($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetEventByEventIdSendsParams()
    {
        $eventId = '123';

        $query = "
            SELECT `id`, `type`, `datetime`, `metadata`
            FROM `code`
            WHERE `event_id` = :event_id";
        $bindings = [
            'event_id' => $eventId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Code($mockPdo);
        $model->getEventByEventId($eventId);
    }

    public function testGetEventByEventIdReturnsEvent()
    {
        $event = [
            'id' => 1,
            'type' => 'some type',
            'datetime' => '2016-06-30 12:00:00',
            'metdata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($event);

        $model = new Code($mockPdo);
        $result = $model->getEventByEventId('');

        $this->assertSame($event, $result);
    }

    public function testInsertEventSendsParams()
    {
        $eventId = '123';
        $type = 'some type';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $query = "
            INSERT INTO `code` (`event_id`, `type`, `datetime`, `metadata`)
            VALUES (:event_id, :type, :datetime, :metadata)";
        $bindings = [
            'event_id' => $eventId,
            'type' => $type,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAffected')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Code($mockPdo);
        $model->insertEvent($eventId, $type, $datetime, $metadata);
    }

    public function testInsertEventReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Code($mockPdo);
        $result = $model->insertEvent('', '', new DateTime(), '');

        $this->assertTrue($result);
    }

    public function testInsertEventReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Code($mockPdo);
        $result = $model->insertEvent('', '', new DateTime(), '');

        $this->assertFalse($result);
    }
}
