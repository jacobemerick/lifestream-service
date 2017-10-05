<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdclass;

use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;

class ProcessTraitTest extends TestCase
{

    public function testGetEventSendsParams()
    {
        $type = 'some type';
        $typeId = 123;

        $eventModel = $this->createMock(EventModel::class);
        $eventModel->expects($this->once())
            ->method('getEventByTypeId')
            ->with(
                $this->equalTo($type),
                $this->equalTo($typeId)
            );

        $processTrait = $this->getMockForTrait(ProcessTrait::class);
        $reflectedTrait = new ReflectionClass($processTrait);
        $reflectedGetEvent = $reflectedTrait->getMethod('getEvent');
        $reflectedGetEvent->setAccessible(true);

        $reflectedGetEvent->invokeArgs($processTrait, [
            $eventModel,
            $type,
            $typeId,
        ]);
    }

    public function testGetEventReturnsEvent()
    {
        $event = [
            'id' => 1,
            'name' => 'some event',
        ];

        $eventModel = $this->createMock(EventModel::class);
        $eventModel->method('getEventByTypeId')
            ->willReturn($event);

        $processTrait = $this->getMockForTrait(ProcessTrait::class);
        $reflectedTrait = new ReflectionClass($processTrait);
        $reflectedGetEvent = $reflectedTrait->getMethod('getEvent');
        $reflectedGetEvent->setAccessible(true);

        $result = $reflectedGetEvent->invokeArgs($processTrait, [
            $eventModel,
            '',
            '',
        ]);

        $this->assertSame($event, $result);
    }

    public function testInsertEventUsesTypeToGetTypeId()
    {
        $type = 'some type';
        $typeId = 123;

        $eventModel = $this->createMock(EventModel::class);
        $eventModel->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo($typeId),
                $this->anything()
            );

        $typeModel = $this->createMock(TypeModel::class);
        $typeModel->expects($this->once())
            ->method('getTypeId')
            ->with(
                $this->equalTo($type)
            )
            ->willReturn($typeId);

        $userModel = $this->createMock(UserModel::class);

        $processTrait = $this->getMockForTrait(ProcessTrait::class);
        $reflectedTrait = new ReflectionClass($processTrait);
        $reflectedInsertEvent = $reflectedTrait->getMethod('insertEvent');
        $reflectedInsertEvent->setAccessible(true);

        $reflectedInsertEvent->invokeArgs($processTrait, [
            $eventModel,
            $typeModel,
            $userModel,
            '',
            '',
            new DateTime,
            new stdclass,
            '',
            $type,
            null,
        ]);
    }

    public function testInsertEventUsesUserToGetUserId()
    {
        $user = 'some user';
        $userId = 123;

        $eventModel = $this->createMock(EventModel::class);
        $eventModel->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->equalTo($userId),
                $this->anything(),
                $this->anything()
            );

        $typeModel = $this->createMock(TypeModel::class);

        $userModel = $this->createMock(UserModel::class);
        $userModel->expects($this->once())
            ->method('getUserId')
            ->with(
                $this->equalTo($user)
            )
            ->willReturn($userId);

        $processTrait = $this->getMockForTrait(ProcessTrait::class);
        $reflectedTrait = new ReflectionClass($processTrait);
        $reflectedInsertEvent = $reflectedTrait->getMethod('insertEvent');
        $reflectedInsertEvent->setAccessible(true);

        $reflectedInsertEvent->invokeArgs($processTrait, [
            $eventModel,
            $typeModel,
            $userModel,
            '',
            '',
            new DateTime,
            new stdclass,
            $user,
            '',
            null,
        ]);
    }

    public function testInsertEventSendsParams()
    {
        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
        $datetime = new DateTime;
        $metadata = (object) [ 'some metadata' ];
        $typeLookupId = 123;

        $eventModel = $this->createMock(EventModel::class);
        $eventModel->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->equalTo($description),
                $this->equalTo($descriptionHtml),
                $this->equalTo($datetime),
                $this->equalTo(json_encode($metadata)),
                $this->anything(),
                $this->anything(),
                $this->equalTo($typeLookupId)
            );

        $typeModel = $this->createMock(TypeModel::class);
        $userModel = $this->createMock(UserModel::class);

        $processTrait = $this->getMockForTrait(ProcessTrait::class);
        $reflectedTrait = new ReflectionClass($processTrait);
        $reflectedInsertEvent = $reflectedTrait->getMethod('insertEvent');
        $reflectedInsertEvent->setAccessible(true);

        $reflectedInsertEvent->invokeArgs($processTrait, [
            $eventModel,
            $typeModel,
            $userModel,
            $description,
            $descriptionHtml,
            $datetime,
            $metadata,
            '',
            '',
            $typeLookupId,
        ]);
    }

    public function testInsertEventReturnsModelResult()
    {
        $modelResult = true;

        $eventModel = $this->createMock(EventModel::class);
        $eventModel->method('insertEvent')
            ->willReturn($modelResult);

        $typeModel = $this->createMock(TypeModel::class);
        $userModel = $this->createMock(UserModel::class);

        $processTrait = $this->getMockForTrait(ProcessTrait::class);
        $reflectedTrait = new ReflectionClass($processTrait);
        $reflectedInsertEvent = $reflectedTrait->getMethod('insertEvent');
        $reflectedInsertEvent->setAccessible(true);

        $result = $reflectedInsertEvent->invokeArgs($processTrait, [
            $eventModel,
            $typeModel,
            $userModel,
            '',
            '',
            new DateTime,
            new stdclass,
            '',
            '',
            null,
        ]);

        $this->assertSame($modelResult, $result);
    }
}
