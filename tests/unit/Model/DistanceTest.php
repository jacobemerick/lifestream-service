<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class DistanceTest extends TestCase
{

    public function testIsInstanceOfDistance()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Distance($mockPdo);

        $this->assertInstanceOf(Distance::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Distance($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetEntryByEntryIdSendsParams()
    {
        $entryId = '123';

        $query = "
            SELECT `id`, `type`, `datetime`, `metadata`
            FROM `distance`
            WHERE `entry_id` = :entry_id";
        $bindings = [
            'entry_id' => $entryId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Distance($mockPdo);
        $model->getEntryByEntryId($entryId);
    }

    public function testGetEntryByEntryIdReturnsEntry()
    {
        $entry = [
            'id' => 1,
            'type' => 'some type',
            'datetime' => '2016-06-30 12:00:00',
            'metdata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($entry);

        $model = new Distance($mockPdo);
        $result = $model->getEntryByEntryId('');

        $this->assertSame($entry, $result);
    }

    public function testInsertEntrySendsParams()
    {
        $entryId = '123';
        $type = 'some type';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $query = "
            INSERT INTO `distance` (`entry_id`, `type`, `datetime`, `metadata`)
            VALUES (:entry_id, :type, :datetime, :metadata)";
        $bindings = [
            'entry_id' => $entryId,
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

        $model = new Distance($mockPdo);
        $model->insertEntry($entryId, $type, $datetime, $metadata);
    }

    public function testInsertEntryReturnsTrueIfSuccess()
    {
        $entryId = '123';
        $type = 'some type';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Distance($mockPdo);
        $result = $model->insertEntry($entryId, $type, $datetime, $metadata);

        $this->assertTrue($result);
    }

    public function testInsertEntryReturnsFalseIfFailure()
    {
        $entryId = '123';
        $type = 'some type';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Distance($mockPdo);
        $result = $model->insertEntry($entryId, $type, $datetime, $metadata);

        $this->assertFalse($result);
    }
}
