<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use ReflectionClass;
use stdclass;

use PHPUnit\Framework\TestCase;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Video as VideoModel;
use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class VideoTest extends TestCase
{

    public function testIsInstanceOfVideo()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Video($mockContainer);

        $this->assertInstanceOf(Video::class, $cron);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Video($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $cron);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Video($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $cron);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Video($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Video($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $cron);
    }

    public function testRunFetchesVideos()
    {
        $mockVideoModel = $this->createMock(VideoModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'videoModel', $mockVideoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchVideos',
                'processVideo',
            ])
            ->getMock();
        $video->expects($this->once())
            ->method('fetchVideos')
            ->with($mockVideoModel)
            ->willReturn([]);
        $video->expects($this->never())
            ->method('processVideo');

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $video->run();
    }

    public function testRunLogsThrownExceptionsFromFetchVideos()
    {
        $mockExceptionMessage = 'Failed to fetch videos';
        $mockException = new Exception($mockExceptionMessage);

        $mockVideoModel = $this->createMock(VideoModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'videoModel', $mockVideoModel ],
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
                'fetchVideos',
                'processVideo',
            ])
            ->getMock();
        $video->method('fetchVideos')
            ->will($this->throwException($mockException));
        $video->expects($this->never())
            ->method('processVideo');

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $video->run();
    }

    public function testRunProcessVideoForEachVideos()
    {
        $videos = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockVideoModel = $this->createMock(VideoModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'videoModel', $mockVideoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchVideos',
                'processVideo',
            ])
            ->getMock();
        $video->method('fetchVideos')
            ->willReturn($videos);
        $video->expects($this->exactly(count($videos)))
            ->method('processVideo')
            ->withConsecutive(
                [ $videos[0] ],
                [ $videos[1] ]
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

    public function testFetchVideosPullsFromModel()
    {
        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->expects($this->once())
            ->method('getVideos');

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedFetchVideosMethod = $reflectedVideo->getMethod('fetchVideos');
        $reflectedFetchVideosMethod->setAccessible(true);

        $reflectedFetchVideosMethod->invokeArgs($video, [
            $mockVideoModel,
        ]);
    }

    public function testFetchVideosReturnsItems()
    {
        $videos = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockVideoModel = $this->createMock(VideoModel::class);
        $mockVideoModel->method('getVideos')
            ->willReturn($videos);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedFetchVideosMethod = $reflectedVideo->getMethod('fetchVideos');
        $reflectedFetchVideosMethod->setAccessible(true);

        $result = $reflectedFetchVideosMethod->invokeArgs($video, [
            $mockVideoModel,
        ]);

        $this->assertEquals($videos, $result);
    }

    public function testProcessVideoGetsEvent()
    {
        $videoData = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getEvent',
                'insertVideo',
            ])
            ->getMock();
        $video->method('getEvent')
            ->with(
                $mockEventModel,
                'video',
                $videoData['id']
            )
            ->willReturn($event);
        $video->expects($this->never())
            ->method('insertVideo');

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $reflectedProcessVideoMethod = $reflectedVideo->getMethod('processVideo');
        $reflectedProcessVideoMethod->setAccessible(true);

        $reflectedProcessVideoMethod->invokeArgs($video, [
            $videoData,
        ]);
    }

    public function testProcessVideoInsertsVideoIfEventNotExists()
    {
        $videoData = [
            'id' => 1,
        ];

        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with('Added video event: 1');
        $mockLogger->expects($this->never())
            ->method('error');

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getEvent',
                'insertVideo',
            ])
            ->getMock();
        $video->method('getEvent')
            ->willReturn(false);
        $video->expects($this->once())
            ->method('insertVideo')
            ->with(
                $videoData
            );

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $reflectedProcessVideoMethod = $reflectedVideo->getMethod('processVideo');
        $reflectedProcessVideoMethod->setAccessible(true);

        $reflectedProcessVideoMethod->invokeArgs($video, [
            $videoData,
        ]);
    }

    public function testProcessVideoFailsIfInsertVideoFails()
    {
        $mockExceptionMessage = 'Failed to insert video';
        $mockException = new Exception($mockExceptionMessage);

        $videoData = [
            'id' => 1,
        ];

        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
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
                'getEvent',
                'insertVideo',
            ])
            ->getMock();
        $video->method('getEvent')
            ->willReturn(false);
        $video->method('insertVideo')
            ->will($this->throwException($mockException));

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $reflectedProcessVideoMethod = $reflectedVideo->getMethod('processVideo');
        $reflectedProcessVideoMethod->setAccessible(true);

        $result = $reflectedProcessVideoMethod->invokeArgs($video, [
            $videoData,
        ]);

        $this->assertFalse($result);
    }

    public function testProcessVideoReturnsTrueIfInsertVideoSucceeds()
    {
        $videoData = [
            'id' => 1,
        ];

        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getEvent',
                'insertVideo',
            ])
            ->getMock();
        $video->method('getEvent')
            ->willReturn(false);

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $reflectedProcessVideoMethod = $reflectedVideo->getMethod('processVideo');
        $reflectedProcessVideoMethod->setAccessible(true);

        $result = $reflectedProcessVideoMethod->invokeArgs($video, [
            $videoData,
        ]);

        $this->assertTrue($result);
    }

    public function testProcessVideoReturnsFalseIfNoChangeMade()
    {
        $videoData = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getEvent',
                'insertVideo',
            ])
            ->getMock();
        $video->method('getEvent')
            ->willReturn($event);
        $video->expects($this->never())
            ->method('insertVideo');

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedLoggerProperty = $reflectedVideo->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($video, $mockLogger);

        $reflectedProcessVideoMethod = $reflectedVideo->getMethod('processVideo');
        $reflectedProcessVideoMethod->setAccessible(true);

        $result = $reflectedProcessVideoMethod->invokeArgs($video, [
            $videoData,
        ]);

        $this->assertFalse($result);
    }

    public function testInsertVideoGetsDescription()
    {
        $metadata = (object) [
            'some key' => 'some value',
        ];

        $videoData = [
            'id' => 1,
            'datetime' => '2016-06-30 12:00:00',
            'metadata' => json_encode($metadata),
        ];

        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDescription',
                'getDescriptionHtml',
                'insertEvent',
            ])
            ->getMock();
        $video->expects($this->once())
            ->method('getDescription')
            ->with($this->equalTo($metadata));

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $reflectedInsertVideoMethod->invokeArgs($video, [
            $videoData,
            $metadata,
        ]);
    }

    public function testInsertVideoGetsDescriptionHtml()
    {
        $metadata = (object) [
            'some key' => 'some value',
        ];

        $videoData = [
            'id' => 1,
            'datetime' => '2016-06-30 12:00:00',
            'metadata' => json_encode($metadata),
        ];

        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDescription',
                'getDescriptionHtml',
                'insertEvent',
            ])
            ->getMock();
        $video->expects($this->once())
            ->method('getDescriptionHtml')
            ->with($this->equalTo($metadata));

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $reflectedInsertVideoMethod->invokeArgs($video, [
            $videoData,
            $metadata,
        ]);
    }

    public function testInsertVideoSendsParametersToInsertEvent()
    {
        $metadata = (object) [
            'some key' => 'some value',
        ];

        $datetime = '2016-06-30 12:00:00';

        $videoData = [
            'id' => 1,
            'datetime' => $datetime,
            'metadata' => json_encode($metadata),
        ];

        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';

        $expectedResponse = true;

        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDescription',
                'getDescriptionHtml',
                'insertEvent',
            ])
            ->getMock();
        $video->method('getDescription')
            ->willReturn($description);
        $video->method('getDescriptionHtml')
            ->willReturn($descriptionHtml);
        $video->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->equalTo($mockEventModel),
                $this->equalTo($mockTypeModel),
                $this->equalTo($mockUserModel),
                $this->equalTo($description),
                $this->equalTo($descriptionHtml),
                $this->callback(function ($param) use ($datetime) {
                    return $param->format('Y-m-d H:i:s') === $datetime;
                }),
                $this->callback(function ($metadata) {
                    return $metadata == new stdclass;
                }),
                $this->equalTo('Jacob Emerick'),
                $this->equalTo('video'),
                $this->equalTo($videoData['id'])
            )
            ->willReturn($expectedResponse);

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedContainerProperty = $reflectedVideo->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($video, $mockContainer);

        $reflectedInsertVideoMethod = $reflectedVideo->getMethod('insertVideo');
        $reflectedInsertVideoMethod->setAccessible(true);

        $result = $reflectedInsertVideoMethod->invokeArgs($video, [
            $videoData,
            $metadata,
        ]);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetDescriptionReturnsFormattedDescription()
    {
        $metadata = (object) [
            'snippet' => (object) [
                'title' => 'some video',
            ],
        ];

        $expectedDescription = 'Favorited some video on Youtube';

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();
 
        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedGetDescriptionMethod = $reflectedVideo->getMethod('getDescription');
        $reflectedGetDescriptionMethod->setAccessible(true);

        $result = $reflectedGetDescriptionMethod->invokeArgs($video, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }
 
    public function testGetDescriptionHtmlReturnsFormattedDescription()
    {
        $metadata = (object) [
            'contentDetails' => (object) [
                'videoId' => 'abc123',
            ],
            'snippet' => (object) [
                'title' => 'some video',
            ],
        ];

        $expectedDescription = '';
        $expectedDescription .= '<iframe src="https://www.youtube.com/embed/abc123" frameborder="0" allowfullscreen></iframe>';
        $expectedDescription .= '<p>Favorited <a href="https://youtu.be/abc123" target="_blank" title="YouTube | some video">some video</a> on YouTube.</p>';

        $video = $this->getMockBuilder(Video::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedVideo = new ReflectionClass(Video::class);

        $reflectedGetDescriptionHtmlMethod = $reflectedVideo->getMethod('getDescriptionHtml');
        $reflectedGetDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetDescriptionHtmlMethod->invokeArgs($video, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }
}
