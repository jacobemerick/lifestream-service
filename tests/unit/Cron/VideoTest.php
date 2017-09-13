<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;

use PHPUnit\Framework\TestCase;

use Madcoda\Youtube\Youtube as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Video as VideoModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class VideoTest extends TestCase
{

    public function testIsInstanceOfVideo()
    {
        $mockContainer = $this->createMock(Container::class);
        $video = new Video($mockContainer);

        $this->assertInstanceOf(Video::class, $video);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $video = new Video($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $video);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $video = new Video($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $video);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $video = new Video($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $video);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $video = new Video($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $video);
    }

    public function testRunFetchesVideos()
    {
        $mockConfig = (object) [
            'video' => (object) [
                'playlist' => 'some_playlist',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'videoClient', $mockClient ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkVideoExists',
                'fetchVideos',
                'insertVideo',
            ])
            ->getMock();
        $video->expects($this->never())
            ->method('checkVideoExists');
        $video->expects($this->once())
            ->method('fetchVideos')
            ->with(
                $this->equalTo($mockClient),
                $this->equalTo($mockConfig->video->playlist)
            )
            ->willReturn([]);
        $video->expects($this->never())
            ->method('insertVideo');

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $video->run();
    }
 
    public function testRunLogsThrownExceptionFromFetchVideos()
    {
        $mockExceptionMessage = 'Failed to fetch videos';
        $mockException = new Exception($mockExceptionMessage);

        $mockConfig = (object) [
            'video' => (object) [
                'playlist' => 'some_playlist',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'videoClient', $mockClient ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkVideoExists',
                'fetchVideos',
                'insertVideo',
            ])
            ->getMock();
        $video->expects($this->never())
            ->method('checkVideoExists');
        $video->method('fetchVideos')
            ->will($this->throwException($mockException));
        $video->expects($this->never())
            ->method('insertVideo');

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $video->run();
    }

    public function testRunChecksIfEachVideoExists()
    {
        $videos = [
            (object) [
                'contentDetails' => (object) [
                    'videoId' => '123',
                ],
            ],
            (object) [
                'contentDetails' => (object) [
                    'videoId' => '456',
                ],
            ],
        ];

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'video' => (object) [
                'playlist' => 'some_playlist',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'videoClient', $mockClient ],
                [ 'videoModel', $mockVideoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Processing videos from api result'));
        $mockLogger->expects($this->never())
            ->method('error');

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkVideoExists',
                'fetchVideos',
                'insertVideo',
            ])
            ->getMock();
        $video->expects($this->exactly(count($videos)))
            ->method('checkVideoExists')
            ->withConsecutive(
                [ $mockVideoModel, $videos[0]->contentDetails->videoId ],
                [ $mockVideoModel, $videos[1]->contentDetails->videoId ]
            )
            ->willReturn(true);
        $video->method('fetchVideos')
            ->will($this->onConsecutiveCalls($videos, []));
        $video->expects($this->never())
            ->method('insertVideo');

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $video->run();
    }

    public function testRunPassesOntoInsertIfVideoNotExists()
    {
        $videos = [
            (object) [
                'contentDetails' => (object) [
                    'videoId' => '123',
                ],
            ],
            (object) [
                'contentDetails' => (object) [
                    'videoId' => '456',
                ],
            ],
        ];

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'video' => (object) [
                'playlist' => 'some_playlist',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'videoClient', $mockClient ],
                [ 'videoModel', $mockVideoModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                $this->anything(),
                $this->equalTo("Inserted new video: {$videos[0]->contentDetails->videoId}")
            );
        $mockLogger->expects($this->never())
            ->method('error');

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkVideoExists',
                'fetchVideos',
                'insertVideo',
            ])
            ->getMock();
        $video->expects($this->exactly(count($videos)))
            ->method('checkVideoExists')
            ->withConsecutive(
                [ $mockVideoModel, $videos[0]->contentDetails->videoId ],
                [ $mockVideoModel, $videos[1]->contentDetails->videoId ]
            )
            ->will($this->onConsecutiveCalls(false, true));
        $video->method('fetchVideos')
            ->will($this->onConsecutiveCalls($videos, []));
        $video->expects($this->once())
            ->method('insertVideo')
            ->withConsecutive(
                [ $mockVideoModel, $videos[0], $mockTimezone ]
            );

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $video->run();
    }

    public function testRunLogsThrownExceptionFromInsertVideo()
    {
        $mockExceptionMessage = 'Failed to insert video';
        $mockException = new Exception($mockExceptionMessage);

        $videos = [
            (object) [
                'contentDetails' => (object) [
                    'videoId' => '123',
                ],
            ],
        ];

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockConfig = (object) [
            'video' => (object) [
                'playlist' => 'some_playlist',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'videoClient', $mockClient ],
                [ 'videoModel', $mockVideoModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkVideoExists',
                'fetchVideos',
                'insertVideo',
            ])
            ->getMock();
        $video->expects($this->exactly(count($videos)))
            ->method('checkVideoExists')
            ->withConsecutive(
                [ $mockVideoModel, $videos[0]->contentDetails->videoId ]
            )
            ->willReturn(false);
        $video->method('fetchVideos')
            ->willReturn($videos);
        $video->method('insertVideo')
            ->will($this->throwException($mockException));

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $video->run();
    }

    public function testFetchVideosPullsFromClient()
    {
        $playlist = 'some_playlist';

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('getPlaylistItemsByPlaylistId')
            ->with($this->equalTo($playlist));

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedFetchVideosMethod = $reflectedVideo->getMethod('fetchVideos');
        $reflectedFetchVideosMethod->setAccessible(true);

        $reflectedFetchVideosMethod->invokeArgs($video, [
            $mockClient,
            $playlist,
        ]);
    }

    public function testFetchVideosReturnsVideos()
    {
        $videos = [
            (object) [
                'id' => 1,
            ],
            (object) [
                'id' => 2,
            ],
        ];

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('getPlaylistItemsByPlaylistId')
            ->willReturn($videos);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedFetchVideosMethod = $reflectedVideo->getMethod('fetchVideos');
        $reflectedFetchVideosMethod->setAccessible(true);

        $result = $reflectedFetchVideosMethod->invokeArgs($video, [
            $mockClient,
            '',
        ]);

        $this->assertEquals($videos, $result);
    }

    public function testCheckVideoExistsPullsFromVideoModel()
    {
        $videoId = '123';

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->expects($this->once())
            ->method('getVideoByVideoId')
            ->with(
                $this->equalTo($videoId)
            );

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedCheckVideoExistsMethod = $reflectedVideo->getMethod('checkVideoExists');
        $reflectedCheckVideoExistsMethod->setAccessible(true);

        $reflectedCheckVideoExistsMethod->invokeArgs($video, [
            $mockVideoModel,
            $videoId,
        ]);
    }

    public function testCheckVideoExistsReturnsTrueIfRecordExists()
    {
        $video = [
            'id' => '123',
            'video_id' => '123',
        ];

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->method('getVideoByVideoId')
            ->willReturn($video);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedCheckVideoExistsMethod = $reflectedVideo->getMethod('checkVideoExists');
        $reflectedCheckVideoExistsMethod->setAccessible(true);

        $result = $reflectedCheckVideoExistsMethod->invokeArgs($video, [
            $mockVideoModel,
            '',
        ]);

        $this->assertTrue($result);
    }

    public function testCheckVideoExistsReturnsFalsesIfRecordNotExists()
    {
        $video = false;

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->method('getVideoByVideoId')
            ->willReturn($video);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedCheckVideoExistsMethod = $reflectedVideo->getMethod('checkVideoExists');
        $reflectedCheckVideoExistsMethod->setAccessible(true);

        $result = $reflectedCheckVideoExistsMethod->invokeArgs($video, [
            $mockVideoModel,
            '',
        ]);

        $this->assertFalse($result);
    }

    public function testInsertVideoCastsDateToDateTime()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $mockVideo = (object) [
            'contentDetails' => (object) [
                'videoId' => '123',
            ],
            'snippet' => (object) [
                'publishedAt' => $date,
            ],
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->expects($this->once())
            ->method('insertVideo')
            ->with(
                $this->anything(),
                $this->equalTo($dateTime),
                $this->anything()
            )
            ->willReturn(true);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $reflectedInsertVideoMethod->invokeArgs($video, [
            $mockVideoModel,
            $mockVideo,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertVideoSetsDateTimeZone()
    {
        $date = '2016-06-30 12:00:00 +000';
        $timezone = 'America/Phoenix'; // always +700, no DST

        $mockVideo = (object) [
            'contentDetails' => (object) [
                'videoId' => '123',
            ],
            'snippet' => (object) [
                'publishedAt' => $date,
            ],
        ];

        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime($date);
        $dateTime->setTimezone($dateTimeZone);

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->expects($this->once())
            ->method('insertVideo')
            ->with(
                $this->anything(),
                $this->callback(function ($param) use ($dateTime) {
                    return $param->getTimeZone()->getName() == $dateTime->getTimeZone()->getName();
                }),
                $this->anything()
            )
            ->willReturn(true);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $reflectedInsertVideoMethod->invokeArgs($video, [
            $mockVideoModel,
            $mockVideo,
            $dateTimeZone,
        ]);
    }

    public function testInsertVideoSendsParamsToVideoModel()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $id = '123';

        $mockVideo = (object) [
            'contentDetails' => (object) [
                'videoId' => $id,
            ],
            'snippet' => (object) [
                'publishedAt' => $date,
            ],
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->expects($this->once())
            ->method('insertVideo')
            ->with(
                $this->equalTo($id),
                $this->equalTo($dateTime),
                $this->equalTo(json_encode($mockVideo))
            )
            ->willReturn(true);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $reflectedInsertVideoMethod->invokeArgs($video, [
            $mockVideoModel,
            $mockVideo,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to insert video
     */
    public function testInsertVideoThrowsExceptionIfModelThrows()
    {
        $exception = new Exception('Failed to insert video');

        $mockVideo = (object) [
            'contentDetails' => (object) [
                'videoId' => '123',
            ],
            'snippet' => (object) [
                'publishedAt' => '2016-06-30 12:00:00',
            ],
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->method('insertVideo')
            ->will($this->throwException($exception));

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $reflectedInsertVideoMethod->invokeArgs($video, [
            $mockVideoModel,
            $mockVideo,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to insert new video: 123
     */
    public function testInsertVideoThrowsExceptionIfInsertFails()
    {
        $id = '123';

        $mockVideo = (object) [
            'contentDetails' => (object) [
                'videoId' => $id,
            ],
            'snippet' => (object) [
                'publishedAt' => '2016-06-30 12:00:00',
            ],
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->method('insertVideo')
            ->willReturn(false);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $reflectedInsertVideoMethod->invokeArgs($video, [
            $mockVideoModel,
            $mockVideo,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertVideoReturnsTrueIfInsertSucceeds()
    {
        $mockVideo = (object) [
            'contentDetails' => (object) [
                'videoId' => '123',
            ],
            'snippet' => (object) [
                'publishedAt' => '2016-06-30 12:00:00',
            ],
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->method('insertVideo')
            ->willReturn(true);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);
        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $result = $reflectedInsertVideoMethod->invokeArgs($video, [
            $mockVideoModel,
            $mockVideo,
            $mockDateTimeZone,
        ]);

        $this->assertTrue($result);
    }
}
