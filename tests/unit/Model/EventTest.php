<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{

    public function testIsInstanceOfEvent()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Event($mockPdo);

        $this->assertInstanceOf(Event::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Event($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetEventsSendsParams()
    {
        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user_id`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE 1 = 1";
        $bindings = [];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEvents();
    }

    public function testGetEventsHandlesLimit()
    {
        $limit = 10;

        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user_id`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE 1 = 1
                LIMIT 0, {$limit}";
        $bindings = [];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEvents($limit);
    }

    public function testGetEventsHandlesLimitAndOffset()
    {
        $limit = 10;
        $offset = 20;

        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user_id`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE 1 = 1
                LIMIT {$offset}, {$limit}";
        $bindings = [];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEvents($limit, $offset);
    }

    public function testGetEventsHandlesType()
    {
        $type = 'book';

        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user_id`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE 1 = 1 AND
                `type`.`name` = :type_name";
        $bindings = [
            'type_name' => $type,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEvents(0, 0, $type);
    }

    public function testGetEventsHandlesUser()
    {
        $user = 'John Doe';

        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user_id`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE 1 = 1 AND
                `user`.`name` = :user_name";
        $bindings = [
            'user_name' => $user,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEvents(0, 0, '', $user);
    }
 
    public function testGetEventsHandlesOrder()
    {
        $order = 'datetime';

        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user_id`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE 1 = 1
                ORDER BY `{$order}` ASC";
        $bindings = [];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEvents(0, 0, '', '', $order);
    }

    public function testGetEventsHandlesOrderAndDescSort()
    {
        $order = 'datetime';
        $isAscending = false;

        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user_id`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE 1 = 1
                ORDER BY `{$order}` DESC";
        $bindings = [];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEvents(0, 0, '', '', $order, $isAscending);
    }
 
    public function testGetEventsReturnsList()
    {
        $types = [
            [
                'id' => 1,
                'description' => 'description one',
            ],
            [
                'id' => 2,
                'description' => 'description two',
            ],
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAll')
            ->willReturn($types);

        $model = new Event($mockPdo);
        $result = $model->getEvents();

        $this->assertSame($types, $result);
    }

    public function testFindByIdSendsParams()
    {
        $eventId = 123;

        $query = "
            SELECT `event`.`id`, `description`, `description_html`, `datetime`, `metadata`,
                   `user`.`id` AS `user_id`, `user`.`name` AS `user_name`,
                   `type`.`id` AS `type_id`, `type`.`name` AS `type_name`
            FROM `event`
            INNER JOIN `user` ON `user`.`id` = `event`.`user_id`
            INNER JOIN `type` ON `type`.`id` = `event`.`type_id`
            WHERE `event`.`id` = :id
            LIMIT 1";
        $bindings = [
            'id' => $eventId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->findById($eventId);
    }

    public function testFindByIdReturnsEvent()
    {
        $event = [
            'id' => 123,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($event);

        $model = new Event($mockPdo);
        $result = $model->findById(null);

        $this->assertSame($event, $result);
    }

    public function testGetEventByTypeIdSendsParams()
    {
        $type = 'type';
        $typeId = 123;

        $query = "
            SELECT `event`.`id`, `description`, `metadata`
            FROM `event`
            INNER JOIN `type` ON
                `type`.`id` = `event`.`type_id` AND
                `type`.`name` = :type
            WHERE `type_lookup_id` = :type_id";
        $bindings = [
            'type' => $type,
            'type_id' => $typeId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEventByTypeId($type, $typeId);
    }

    public function testGetEventByTypeIdReturnsEvent()
    {
        $event = [
            'id' => 123,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($event);

        $model = new Event($mockPdo);
        $result = $model->getEventByTypeId(null, null);

        $this->assertSame($event, $result);
    }

    public function testInsertEventSendsParams()
    {
        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';
        $userId = 1;
        $typeId = 1;
        $typeLookupId = 123;

        $query = "
            INSERT INTO `event`
                (`description`, `description_html`, `datetime`, `metadata`, `user_id`,
                 `type_id`, `type_lookup_id`)
            VALUES
                (:description, :description_html, :datetime, :metadata, :user_id,
                 :type_id, :type_lookup_id)";
        $bindings = [
            'description' => $description,
            'description_html' => $descriptionHtml,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
            'user_id' => $userId,
            'type_id' => $typeId,
            'type_lookup_id' => $typeLookupId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAffected')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->insertEvent(
            $description,
            $descriptionHtml,
            $datetime,
            $metadata,
            $userId,
            $typeId,
            $typeLookupId
        );
    }

    public function testInsertEventReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Event($mockPdo);
        $result = $model->insertEvent('', '', new DateTime(), '', null, null, null);

        $this->assertTrue($result);
    }

    public function testInsertEventReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Event($mockPdo);
        $result = $model->insertEvent('', '', new DateTime(), '', null, null, null);

        $this->assertFalse($result);
    }

    public function testUpdateEventMetadataSendsParams()
    {
        $id = 123;
        $metadata = '{"key":"value"}';

        $query = "
            UPDATE `event`
            SET `metadata` = :metadata
            WHERE `id` = :id
            LIMIT 1";
        $bindings = [
            'id' => $id,
            'metadata' => $metadata,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAffected')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->updateEventMetadata(
            $id,
            $metadata
        );
    }

    public function testUpdateEventMetadataReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Event($mockPdo);
        $result = $model->updateEventMetadata(null, '');

        $this->assertTrue($result);
    }

    public function testUpdateEventMetadataReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Event($mockPdo);
        $result = $model->updateEventMetadata(null, '');

        $this->assertFalse($result);
    }
}
