<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class VideoTest extends TestCase
{

    public function testIsInstanceOfVideo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Video($mockPdo);

        $this->assertInstanceOf(Video::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Video($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetVideoByVideoIdSendsParams()
    {
        $videoId = '123';

        $query = "
            SELECT `id`, `datetime`, `metadata`
            FROM `distance`
            WHERE `video_id` = :video_id";
        $bindings = [
            'video_id' => $videoId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Video($mockPdo);
        $model->getVideoByVideoId($videoId);
    }

    public function testGetVideoByVideoIdReturnsVideo()
    {
        $video = [
            'id' => 1,
            'datetime' => '2016-06-30 12:00:00',
            'metadata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($video);

        $model = new Video($mockPdo);
        $result = $model->getVideoByVideoId('');

        $this->assertSame($video, $result);
    }

    public function testInsertVideoSendsParams()
    {
        $videoId = '123';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $query = "
            INSERT INTO `distance` (`video_id`, `datetime`, `metadata`)
            VALUES (:video_id, :datetime, :metadata)";
        $bindings = [
            'video_id' => $videoId,
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

        $model = new Video($mockPdo);
        $model->insertVideo($videoId, $datetime, $metadata);
    }

    public function testInsertVideoReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Video($mockPdo);
        $result = $model->insertVideo('', new DateTime(), '');

        $this->assertTrue($result);
    }

    public function testInsertVideoReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Video($mockPdo);
        $result = $model->insertVideo('', new DateTime(), '');

        $this->assertFalse($result);
    }
}
