<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use ReflectionClass;
use stdclass;

use PHPUnit\Framework\TestCase;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Photo as PhotoModel;
use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class PhotoTest extends TestCase
{

    public function testIsInstanceOfPhoto()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Photo($mockContainer);

        $this->assertInstanceOf(Photo::class, $cron);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Photo($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $cron);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Photo($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $cron);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Photo($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Photo($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $cron);
    }

    public function testRunFetchesMedia()
    {
        $mockPhotoModel = $this->createMock(PhotoModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'photoModel', $mockPhotoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchMedia',
                'processMedia',
            ])
            ->getMock();
        $photo->expects($this->once())
            ->method('fetchMedia')
            ->with($mockPhotoModel)
            ->willReturn([]);
        $photo->expects($this->never())
            ->method('processMedia');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $photo->run();
    }

    public function testRunLogsThrownExceptionsFromFetchMedia()
    {
        $mockExceptionMessage = 'Failed to fetch media';
        $mockException = new Exception($mockExceptionMessage);

        $mockPhotoModel = $this->createMock(PhotoModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'photoModel', $mockPhotoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchMedia',
                'processMedia',
            ])
            ->getMock();
        $photo->method('fetchMedia')
            ->will($this->throwException($mockException));
        $photo->expects($this->never())
            ->method('processMedia');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $photo->run();
    }

    public function testRunProcessMediaForEachMedia()
    {
        $media = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'photoModel', $mockPhotoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchMedia',
                'processMedia',
            ])
            ->getMock();
        $photo->method('fetchMedia')
            ->willReturn($media);
        $photo->expects($this->exactly(count($media)))
            ->method('processMedia')
            ->withConsecutive(
                [ $media[0] ],
                [ $media[1] ]
            );

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $photo->run();
    }

    public function testFetchMediaPullsFromModel()
    {
        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->expects($this->once())
            ->method('getMedia');

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedFetchMediaMethod = $reflectedPhoto->getMethod('fetchMedia');
        $reflectedFetchMediaMethod->setAccessible(true);

        $reflectedFetchMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
        ]);
    }

    public function testFetchMediaReturnsItems()
    {
        $media = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('getMedia')
            ->willReturn($media);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedFetchMediaMethod = $reflectedPhoto->getMethod('fetchMedia');
        $reflectedFetchMediaMethod->setAccessible(true);

        $result = $reflectedFetchMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
        ]);

        $this->assertEquals($media, $result);
    }

    public function testProcessMediaGetsEvent()
    {
        $media = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
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

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->method('checkMetadataUpdated')
            ->willReturn(false);
        $photo->method('getEvent')
            ->with(
                $mockEventModel,
                'photo',
                $media['id']
            )
            ->willReturn($event);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->expects($this->never())
            ->method('insertMedia');
        $photo->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);
    }

    public function testProcessMediaGetsMediaMetadata()
    {
        $media = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
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

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->method('checkMetadataUpdated')
            ->willReturn(false);
        $photo->method('getEvent')
            ->willReturn($event);
        $photo->expects($this->once())
            ->method('getMediaMetadata')
            ->with($media)
            ->willReturn($mediaMetadata);
        $photo->expects($this->never())
            ->method('insertMedia');
        $photo->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);
    }

    public function testProcessMediaInsertsMediaIfEventNotExists()
    {
        $media = [
            'id' => 1,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
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
            ->with('Added photo event: 1');
        $mockLogger->expects($this->never())
            ->method('error');

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->expects($this->never())
            ->method('checkMetadataUpdated');
        $photo->method('getEvent')
            ->willReturn(false);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->expects($this->once())
            ->method('insertMedia')
            ->with(
                $media,
                $mediaMetadata
            );
        $photo->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);
    }

    public function testProcessMediaFailsIfInsertMediaFails()
    {
        $mockExceptionMessage = 'Failed to insert media';
        $mockException = new Exception($mockExceptionMessage);

        $media = [
            'id' => 1,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
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

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->expects($this->never())
            ->method('checkMetadataUpdated');
        $photo->method('getEvent')
            ->willReturn(false);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->method('insertMedia')
            ->will($this->throwException($mockException));
        $photo->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $result = $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);

        $this->assertFalse($result);
    }

    public function testProcessMediaReturnsTrueIfInsertMediaSucceeds()
    {
        $media = [
            'id' => 1,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
        ];

        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->method('checkMetadataUpdated')
            ->willReturn(false);
        $photo->method('getEvent')
            ->willReturn(false);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $result = $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);

        $this->assertTrue($result);
    }

    public function testProcessMediaChecksMetadataUpdated()
    {
        $media = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
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

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->expects($this->once())
            ->method('checkMetadataUpdated')
            ->with(
                $event,
                $mediaMetadata
            )
            ->willReturn(false);
        $photo->method('getEvent')
            ->willReturn($event);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);
    }

    public function testProcessMediaUpdatesMediaIfMetadataUpdated()
    {
        $media = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
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
            ->with('Updated photo event metadata: 1');
        $mockLogger->expects($this->never())
            ->method('error');

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->method('checkMetadataUpdated')
            ->willReturn(true);
        $photo->method('getEvent')
            ->willReturn($event);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->expects($this->never())
            ->method('insertMedia');
        $photo->expects($this->once())
            ->method('updateEventMetadata')
            ->with(
                $mockEventModel,
                $event['id'],
                $mediaMetadata
            );

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);
    }

    public function testProcessMediaFailsIfUpdateMetadataFails()
    {
        $mockExceptionMessage = 'Failed to update media';
        $mockException = new Exception($mockExceptionMessage);

        $media = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
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

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->method('checkMetadataUpdated')
            ->willReturn(true);
        $photo->method('getEvent')
            ->willReturn($event);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->expects($this->never())
            ->method('insertMedia');
        $photo->method('updateEventMetadata')
            ->will($this->throwException($mockException));

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $result = $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);

        $this->assertFalse($result);
    }

    public function testProcessMediaReturnsTrueIfUpdateMetadataSucceeds()
    {
        $media = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
        ];

        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->method('checkMetadataUpdated')
            ->willReturn(true);
        $photo->method('getEvent')
            ->willReturn($event);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->expects($this->never())
            ->method('insertMedia');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $result = $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);

        $this->assertTrue($result);
    }

    public function testProcessMediaReturnsFalseIfNoChangeMade()
    {
        $media = [
            'id' => 1,
        ];

        $event = [
            'id' => 2,
        ];

        $mediaMetadata = (object) [
            'some key' => 'some value',
        ];

        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMetadataUpdated',
                'getEvent',
                'getMediaMetadata',
                'insertMedia',
                'updateEventMetadata',
            ])
            ->getMock();
        $photo->method('checkMetadataUpdated')
            ->willReturn(false);
        $photo->method('getEvent')
            ->willReturn($event);
        $photo->method('getMediaMetadata')
            ->willReturn($mediaMetadata);
        $photo->expects($this->never())
            ->method('insertMedia');
        $photo->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $result = $reflectedProcessMediaMethod->invokeArgs($photo, [
            $media,
        ]);

        $this->assertFalse($result);
    }

    public function testGetMediaMetadataFormatsMetadata()
    {
        $metadata = (object) [
            'likes' => (object) [
                'count' => 2,
            ],
            'comments' => (object) [
                'count' => 3,
            ],
        ];

        $expectedMetadata = (object) [
            'likes' => 2,
            'comments' => 3,
        ];

        $media = [ 'metadata' => json_encode($metadata) ];

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedGetMediaMetadataMethod = $reflectedPhoto->getMethod('getMediaMetadata');
        $reflectedGetMediaMetadataMethod->setAccessible(true);

        $result = $reflectedGetMediaMetadataMethod->invokeArgs($photo, [
            $media,
        ]);

        $this->assertEquals($expectedMetadata, $result);
    }

    public function testInsertMediaGetsDescription()
    {
        $metadata = (object) [
            'some key' => 'some value',
        ];

        $media = [
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

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDescription',
                'getDescriptionHtml',
                'insertEvent',
            ])
            ->getMock();
        $photo->expects($this->once())
            ->method('getDescription')
            ->with($this->equalTo($metadata));

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $reflectedInsertMediaMethod->invokeArgs($photo, [
            $media,
            $metadata,
        ]);
    }

    public function testInsertMediaGetsDescriptionHtml()
    {
        $metadata = (object) [
            'some key' => 'some value',
        ];

        $media = [
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

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDescription',
                'getDescriptionHtml',
                'insertEvent',
            ])
            ->getMock();
        $photo->expects($this->once())
            ->method('getDescriptionHtml')
            ->with($this->equalTo($metadata));

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $reflectedInsertMediaMethod->invokeArgs($photo, [
            $media,
            $metadata,
        ]);
    }

    public function testInsertMediaSendsParametersToInsertEvent()
    {
        $metadata = (object) [
            'some key' => 'some value',
        ];

        $datetime = '2016-06-30 12:00:00';

        $media = [
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

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDescription',
                'getDescriptionHtml',
                'insertEvent',
            ])
            ->getMock();
        $photo->method('getDescription')
            ->willReturn($description);
        $photo->method('getDescriptionHtml')
            ->willReturn($descriptionHtml);
        $photo->expects($this->once())
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
                $this->equalTo($metadata),
                $this->equalTo('Jacob Emerick'),
                $this->equalTo('photo'),
                $this->equalTo($media['id'])
            )
            ->willReturn($expectedResponse);

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $result = $reflectedInsertMediaMethod->invokeArgs($photo, [
            $media,
            $metadata,
        ]);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testCheckMetadataUpdatedReturnsTrueIfDifferent()
    {
        $metadata = (object) [
            'some key' => 'some value',
        ];

        $newMetadata = (object) [
            'some key' => 'some other value',
        ];

        $event = [ 'metadata' => json_encode($metadata) ];

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedCheckMetadataUpdatedMethod = $reflectedPhoto->getMethod('checkMetadataUpdated');
        $reflectedCheckMetadataUpdatedMethod->setAccessible(true);

        $result = $reflectedCheckMetadataUpdatedMethod->invokeArgs($photo, [
            $event,
            $newMetadata,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckMetadataUpdatedReturnsFalseIfSame()
    {
        $metadata = (object) [
            'some key' => 'some value',
        ];

        $event = [ 'metadata' => json_encode($metadata) ];

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedCheckMetadataUpdatedMethod = $reflectedPhoto->getMethod('checkMetadataUpdated');
        $reflectedCheckMetadataUpdatedMethod->setAccessible(true);

        $result = $reflectedCheckMetadataUpdatedMethod->invokeArgs($photo, [
            $event,
            $metadata,
        ]);

        $this->assertFalse($result);
    }

    public function testSimpleTextBreaksOnNewline()
    {
        $text = "line one\nline two";

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedSimpleTextMethod = $reflectedPhoto->getMethod('simpleText');
        $reflectedSimpleTextMethod->setAccessible(true);

        $result = $reflectedSimpleTextMethod->invokeArgs($photo, [
            $text,
        ]);

        $this->assertEquals('line one', $result);
    }

    public function testSimpleTextTrimsWhitespace()
    {
        $text = 'trim me ';

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedSimpleTextMethod = $reflectedPhoto->getMethod('simpleText');
        $reflectedSimpleTextMethod->setAccessible(true);

        $result = $reflectedSimpleTextMethod->invokeArgs($photo, [
            $text,
        ]);

        $this->assertEquals('trim me', $result);
    }

    public function testSimpleTextHandlesSingleLine()
    {
        $text = 'simple text';

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedSimpleTextMethod = $reflectedPhoto->getMethod('simpleText');
        $reflectedSimpleTextMethod->setAccessible(true);

        $result = $reflectedSimpleTextMethod->invokeArgs($photo, [
            $text,
        ]);

        $this->assertEquals('simple text', $result);
    }

    public function testGetDescriptionCallsSimpleText()
    {
        $metadata = (object) [
            'caption' => (object) [
                'text' => 'some text',
            ],
        ];

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'simpleText',
            ])
            ->getMock();
        $photo->expects($this->once())
            ->method('simpleText')
            ->with($metadata->caption->text);
 
        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedGetDescriptionMethod = $reflectedPhoto->getMethod('getDescription');
        $reflectedGetDescriptionMethod->setAccessible(true);

        $reflectedGetDescriptionMethod->invokeArgs($photo, [
            $metadata,
        ]);
    }

    public function testGetDescriptionReturnsFormattedDescription()
    {
        $metadata = (object) [
            'caption' => (object) [
                'text' => 'some text',
            ],
        ];

        $simpleText = 'some simple text';

        $expectedDescription = 'Shared a photo | some simple text';

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'simpleText',
            ])
            ->getMock();
        $photo->method('simpleText')
            ->willReturn($simpleText);
 
        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedGetDescriptionMethod = $reflectedPhoto->getMethod('getDescription');
        $reflectedGetDescriptionMethod->setAccessible(true);

        $result = $reflectedGetDescriptionMethod->invokeArgs($photo, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }
 
    public function testGetDescriptionHtmlCallsSimpleText()
    {
        $metadata = (object) [
            'images' => (object) [
                'standard_resolution' => (object) [
                    'url' => 'some-image.jpg',
                    'height' => 640,
                    'width' => 800,
                ],
            ],
            'caption' => (object) [
                'text' => 'some text',
            ],
        ];

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'simpleText',
            ])
            ->getMock();
        $photo->expects($this->exactly(2))
            ->method('simpleText')
            ->with($metadata->caption->text);

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedGetDescriptionHtmlMethod = $reflectedPhoto->getMethod('getDescriptionHtml');
        $reflectedGetDescriptionHtmlMethod->setAccessible(true);

        $reflectedGetDescriptionHtmlMethod->invokeArgs($photo, [
            $metadata,
        ]);
    }

    public function testGetDescriptionHtmlReturnsFormattedDescription()
    {
        $metadata = (object) [
            'images' => (object) [
                'standard_resolution' => (object) [
                    'url' => 'some-image.jpg',
                    'height' => 640,
                    'width' => 800,
                ],
            ],
            'caption' => (object) [
                'text' => 'some text',
            ],
        ];

        $simpleText = 'some simple text';

        $expectedDescription = '';
        $expectedDescription .= '<img src="some-image.jpg" alt="some simple text" height="640" width="800" />';
        $expectedDescription .= '<p>some simple text</p>';

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'simpleText',
            ])
            ->getMock();
        $photo->method('simpleText')
            ->willReturn($simpleText);

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedGetDescriptionHtmlMethod = $reflectedPhoto->getMethod('getDescriptionHtml');
        $reflectedGetDescriptionHtmlMethod->setAccessible(true);

        $result = $reflectedGetDescriptionHtmlMethod->invokeArgs($photo, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }
}
