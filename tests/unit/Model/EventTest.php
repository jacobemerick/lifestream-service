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
            INNER JOIN `user` ON `user`.`id` = `event`.`user`
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
            INNER JOIN `user` ON `user`.`id` = `event`.`user`
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
            INNER JOIN `user` ON `user`.`id` = `event`.`user`
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
            INNER JOIN `user` ON `user`.`id` = `event`.`user`
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
            INNER JOIN `user` ON `user`.`id` = `event`.`user`
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
            INNER JOIN `user` ON `user`.`id` = `event`.`user`
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
            INNER JOIN `user` ON `user`.`id` = `event`.`user`
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

    public function testGetEventByTypeSendsParams()
    {
        $typeId = 1;
        $typeLookupId = 123;

        $query = "
            SELECT `id`
            FROM `event`
            WHERE `type_id` = :type_id AND
                  `type_lookup_id` = :type_lookup_id";
        $bindings = [
            'type_id' => $typeId,
            'type_lookup_id' => $typeLookupId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Event($mockPdo);
        $model->getEventByType($typeId, $typeLookupId);
    }

    public function testGetEventByTypeReturnsEvent()
    {
        $event = [
            'id' => 123,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($event);

        $model = new Event($mockPdo);
        $result = $model->getEventByType(null, null);

        $this->assertSame($event, $result);
    }

    public function testInsertEventSendsParams()
    {
        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';
        $typeId = 1;
        $typeLookupId = 123;

        $query = "
            INSERT INTO `event` (`description`, `description_html`, `datetime`, `metadata`,
                                 `type_id`, `type_lookup_id`)
            VALUES (:description, :description_html, :datetime, :metadata, :type_id, :type_lookup_id)";
        $bindings = [
            'description' => $description,
            'description_html' => $descriptionHtml,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
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
        $model->insertEvent($description, $descriptionHtml, $datetime, $metadata, $typeId, $typeLookupId);
    }

    public function testInsertEventReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Event($mockPdo);
        $result = $model->insertEvent('', '', new DateTime(), '', null, null);

        $this->assertTrue($result);
    }

    public function testInsertEventReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Event($mockPdo);
        $result = $model->insertEvent('', '', new DateTime(), '', null, null);

        $this->assertFalse($result);
    }
}
