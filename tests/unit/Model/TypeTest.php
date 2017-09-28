<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{

    public function testIsInstanceOfType()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Type($mockPdo);

        $this->assertInstanceOf(Type::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Type($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetTypesReturnsList()
    {
        $types = [
            [
                'id' => 1,
                'name' => 'type one',
            ],
            [
                'id' => 2,
                'name' => 'type two',
            ],
        ];

        $query = "
            SELECT `id`, `name`
            FROM `type`
            ORDER BY `name` ASC";

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo($query))
            ->willReturn($types);

        $model = new Type($mockPdo);
        $result = $model->getTypes();

        $this->assertSame($types, $result);
    }

    public function testGetTypeIdSendsParams()
    {
        $type = 'some type';

        $query = "
            SELECT `id`
            FROM `type`
            WHERE `name` = :type
            LIMIT 1";
        $bindings = [
            'type' => $type,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchValue')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Type($mockPdo);
        $model->getTypeId($type);
    }

    public function testGetTypeIdReturnsId()
    {
        $typeId = 1;

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchValue')
            ->willReturn($typeId);

        $model = new Type($mockPdo);
        $result = $model->getTypeId('');

        $this->assertSame($typeId, $result);
    }
}
