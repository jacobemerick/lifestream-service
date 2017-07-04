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

    public function testGetTypesSendsParams()
    {
        $query = "
            SELECT `id`, `name`
            FROM `type`
            ORDER BY `name` ASC";

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo($query));

        $model = new Type($mockPdo);
        $model->getTypes();
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

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAll')
            ->willReturn($types);

        $model = new Type($mockPdo);
        $result = $model->getTypes();

        $this->assertSame($types, $result);
    }
}
