<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
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
            INNER JOIN `type` ON `type`.`id` = `event`.`type`
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
            INNER JOIN `type` ON `type`.`id` = `event`.`type`
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
            INNER JOIN `type` ON `type`.`id` = `event`.`type`
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
            INNER JOIN `type` ON `type`.`id` = `event`.`type`
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
            INNER JOIN `type` ON `type`.`id` = `event`.`type`
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
            INNER JOIN `type` ON `type`.`id` = `event`.`type`
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
            INNER JOIN `type` ON `type`.`id` = `event`.`type`
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
}
