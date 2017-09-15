<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class PhotoTest extends TestCase
{

    public function testIsInstanceOfPhoto()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Photo($mockPdo);

        $this->assertInstanceOf(Photo::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Photo($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetMediaByMediaIdSendsParams()
    {
        $mediaId = '123';

        $query = "
            SELECT `id`, `datetime`, `metadata`
            FROM `photo`
            WHERE `media_id` = :media_id";
        $bindings = [
            'media_id' => $mediaId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Photo($mockPdo);
        $model->getMediaByMediaId($mediaId);
    }

    public function testGetMediaByMediaIdReturnsMedia()
    {
        $media = [
            'id' => 1,
            'datetime' => '2016-06-30 12:00:00',
            'metadata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($media);

        $model = new Photo($mockPdo);
        $result = $model->getMediaByMediaId('');

        $this->assertSame($media, $result);
    }

    public function testInsertMediaSendsParams()
    {
        $mediaId = '123';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $query = "
            INSERT INTO `photo` (`media_id`, `datetime`, `metadata`)
            VALUES (:media_id, :datetime, :metadata)";
        $bindings = [
            'media_id' => $mediaId,
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

        $model = new Photo($mockPdo);
        $model->insertMedia($mediaId, $datetime, $metadata);
    }

    public function testInsertMediaReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Photo($mockPdo);
        $result = $model->insertMedia('', new DateTime(), '');

        $this->assertTrue($result);
    }

    public function testInsertMediaReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Photo($mockPdo);
        $result = $model->insertMedia('', new DateTime(), '');

        $this->assertFalse($result);
    }

    public function testUpdateMediaSendsParams()
    {
        $mediaId = '123';
        $metadata = '{"key":"value"}';

        $query = "
            UPDATE `photo`
            SET `metadata` = :metadata
            WHERE `media_id` = :media_id
            LIMIT 1";
        $bindings = [
            'media_id' => $mediaId,
            'metadata' => $metadata,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAffected')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Photo($mockPdo);
        $model->updateMedia($mediaId, $metadata);
    }

    public function testUpdateMediaReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Photo($mockPdo);
        $result = $model->updateMedia('', '');

        $this->assertTrue($result);
    }

    public function testUpdateMediaReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Photo($mockPdo);
        $result = $model->updateMedia('', '');

        $this->assertFalse($result);
    }
}
