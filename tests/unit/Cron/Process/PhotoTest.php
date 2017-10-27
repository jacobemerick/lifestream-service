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
        $this->markTestIncomplete();
    }

    public function testProcessMediaFailsIfInsertMediaFails()
    {
        $this->markTestIncomplete();
    }

    public function testProcessMediaReturnsTrueIfInsertMediaSucceeds()
    {
        $this->markTestIncomplete();
    }

    public function testProcessMediaChecksMetadataUpdated()
    {
        $this->markTestIncomplete();
    }

    public function testProcessMediaUpdatesMediaIfMetadataUpdated()
    {
        $this->markTestIncomplete();
    }

    public function testProcessMediaFailsIfUpdateMetadataFails()
    {
        $this->markTestIncomplete();
    }

    public function testProcessMediaReturnsTrueIfUpdateMetadataSucceeds()
    {
        $this->markTestIncomplete();
    }

    public function testProcessMediaReturnsFalseIfNoChangeMade()
    {
        $this->markTestIncomplete();
    }

    public function testGetMediaMetadataFormatsMetadata()
    {
        $this->markTestIncomplete();
    }

    public function testInsertMediaGetsDescription()
    {
        $this->markTestIncomplete();
    }

    public function testInsertMediaGetsDescriptionHtml()
    {
        $this->markTestIncomplete();
    }

    public function testInsertMediaSendsParametersToInsertEvent()
    {
        $this->markTestIncomplete();
    }

    public function testCheckMetadataUpdatedReturnsTrueIfDifferent()
    {
        $this->markTestIncomplete();
    }

    public function testCheckMetadataUpdatedReturnsFalseIfSame()
    {
        $this->markTestIncomplete();
    }

    public function testSimpleTextBreaksOnNewline()
    {
        $this->markTestIncomplete();
    }

    public function testSimpleTextTrimsWhitespace()
    {
        $this->markTestIncomplete();
    }

    public function testSimpleTextHandlesSingleLine()
    {
        $this->markTestIncomplete();
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
