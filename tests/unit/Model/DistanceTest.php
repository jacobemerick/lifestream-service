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
            'metadata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($entry);

        $model = new Distance($mockPdo);
        $result = $model->getEntryByEntryId('');

        $this->assertSame($entry, $result);
    }

    public function testGetEntriesReturnsEntries()
    {
        $entries = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $query = "
            SELECT `id`, `entry_id`, `type`, `datetime`, `metadata`
            FROM `distance`";

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query)
            )
            ->willReturn($entries);

        $model = new Distance($mockPdo);
        $result = $model->getEntries();

        $this->assertSame($entries, $result);
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
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Distance($mockPdo);
        $result = $model->insertEntry('', '', new DateTime(), '');

        $this->assertTrue($result);
    }

    public function testInsertEntryReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Distance($mockPdo);
        $result = $model->insertEntry('', '', new DateTime(), '');

        $this->assertFalse($result);
    }
}
