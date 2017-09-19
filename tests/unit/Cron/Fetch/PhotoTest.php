<?php

namespace Jacobemerick\LifestreamService\Cron\Fetch;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;
use stdclass;

use PHPUnit\Framework\TestCase;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Photo as PhotoModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class PhotoTest extends TestCase
{

    public function testIsInstanceOfPhoto()
    {
        $mockContainer = $this->createMock(Container::class);
        $photo = new Photo($mockContainer);

        $this->assertInstanceOf(Photo::class, $photo);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $photo = new Photo($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $photo);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $photo = new Photo($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $photo);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $photo = new Photo($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $photo);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $photo = new Photo($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $photo);
    }

    public function testRunFetchesMedia()
    {
        $mockConfig = (object) [
            'photo' => (object) [
                'token' => 'some token',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'photoClient', $mockClient ],
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
            ->with(
                $this->equalTo($mockClient),
                $this->equalTo($mockConfig->photo->token),
                $this->anything()
            )
            ->willReturn([]);

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $photo->run();
    }

    public function testRunLogsThrownExceptionFromFetchMedia()
    {
        $mockExceptionMessage = 'Failed to fetch media';
        $mockException = new Exception($mockExceptionMessage);

        $mockConfig = (object) [
            'photo' => (object) [
                'token' => 'some token',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'photoClient', $mockClient ],
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

    public function testRunProcessesEachMedia()
    {
        $media = [
            (object) [
                'id' => '123',
            ],
            (object) [
                'id' => '456',
            ],
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'photo' => (object) [
                'token' => 'some token',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'photoClient', $mockClient ],
                [ 'photoModel', $mockPhotoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
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
            ->will($this->onConsecutiveCalls($media, []));
        $photo->expects($this->exactly(count($media)))
            ->method('processMedia')
            ->withConsecutive(
                [ $mockPhotoModel, $media[0] ],
                [ $mockPhotoModel, $media[1] ]
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

    public function testRunLogsThrownExceptionFromProcessMedia()
    {
        $mockExceptionMessage = 'Failed to insert media';
        $mockException = new Exception($mockExceptionMessage);

        $media = [
            (object) [
                'id' => '123',
            ],
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'photo' => (object) [
                'token' => 'some token',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'photoClient', $mockClient ],
                [ 'photoModel', $mockPhotoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchMedia',
                'processMedia',
            ])
            ->getMock();
        $photo->method('fetchMedia')
            ->willReturn($media);
        $photo->method('processMedia')
            ->will($this->throwException($mockException));

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $photo->run();
    }

    public function testRunMakesSingleRequestIfNoMedia()
    {
        $media = [];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'photo' => (object) [
                'token' => 'some token',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'photoClient', $mockClient ],
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
        $photo->expects($this->once())
            ->method('fetchMedia')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(null)
            )
            ->willReturn($media);
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

    public function testRunMakesRepeatRequestUsingCursor()
    {
        $media = [
            (object) [
                'id' => '123',
            ],
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'photo' => (object) [
                'token' => 'some token',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'photoClient', $mockClient ],
                [ 'photoModel', $mockPhotoModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Processing page 1 of api results'));
        $mockLogger->expects($this->never())
            ->method('error');

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchMedia',
                'processMedia',
            ])
            ->getMock();
        $photo->expects($this->exactly(2))
            ->method('fetchMedia')
            ->withConsecutive(
                [ $this->anything(), $this->anything(), $this->equalTo(null) ],
                [ $this->anything(), $this->anything(), $this->equalTo($media[0]->id) ]
            )
            ->will($this->onConsecutiveCalls($media, []));

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedContainerProperty = $reflectedPhoto->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($photo, $mockContainer);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $photo->run();
    }

    public function testFetchMediaPullsFromClient()
    {
        $token = 'some token';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn('{"data":[]}');
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('users/self/media/recent'),
                $this->equalTo([
                    'query' => [
                        'access_token' => $token,
                    ],
                ])
            )
            ->willReturn($mockResponse);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedFetchMediaMethod = $reflectedPhoto->getMethod('fetchMedia');
        $reflectedFetchMediaMethod->setAccessible(true);

        $reflectedFetchMediaMethod->invokeArgs($photo, [
            $mockClient,
            $token,
            null,
        ]);
    }

    public function testFetchMediaPassesMaxIdToClient()
    {
        $token = 'some token';
        $maxId = '123';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn('{"data":[]}');
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('users/self/media/recent'),
                $this->equalTo([
                    'query' => [
                        'access_token' => $token,
                        'max_id' => $maxId,
                    ],
                ])
            )
            ->willReturn($mockResponse);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedFetchMediaMethod = $reflectedPhoto->getMethod('fetchMedia');
        $reflectedFetchMediaMethod->setAccessible(true);

        $reflectedFetchMediaMethod->invokeArgs($photo, [
            $mockClient,
            $token,
            $maxId,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to fetch media: 400
     */
    public function testFetchMediaThrowsExceptionOnNon200Status()
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->never())
            ->method('getBody');
        $mockResponse->method('getStatusCode')
            ->willReturn(400);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('request')
            ->willReturn($mockResponse);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedFetchMediaMethod = $reflectedPhoto->getMethod('fetchMedia');
        $reflectedFetchMediaMethod->setAccessible(true);

        $reflectedFetchMediaMethod->invokeArgs($photo, [
            $mockClient,
            '',
            null,
        ]);
    }

    public function testFetchMediaReturnsMedia()
    {
        $media = [
            (object) [
                'id' => 1,
            ],
            (object) [
                'id' => 2,
            ],
        ];
        $jsonMedia = json_encode($media);

        $json = "{\"data\":{$jsonMedia}}";

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($json);
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('request')
            ->willReturn($mockResponse);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedFetchMediaMethod = $reflectedPhoto->getMethod('fetchMedia');
        $reflectedFetchMediaMethod->setAccessible(true);

        $result = $reflectedFetchMediaMethod->invokeArgs($photo, [
            $mockClient,
            '',
            null,
        ]);

        $this->assertEquals($media, $result);
    }

    public function testProcessMediaChecksMediaExists()
    {
        $media = (object) [
            'id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMediaExists',
                'checkMediaUpdated',
                'insertMedia',
                'updateMedia',
            ])
            ->getMock();
        $photo->expects($this->once())
            ->method('checkMediaExists')
            ->with(
                $this->equalTo($mockPhotoModel),
                $this->equalTo($media->id)
            )
            ->willReturn(true);
        $photo->expects($this->never())
            ->method('insertMedia');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $reflectedProcessMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $media,
        ]);
    }

    public function testProcessMediaInsertsIfMediaDoesNotExist()
    {
        $media = (object) [
            'id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('Inserted new media: 123')
            );

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMediaExists',
                'checkMediaUpdated',
                'insertMedia',
                'updateMedia',
            ])
            ->getMock();
        $photo->method('checkMediaExists')
            ->willReturn(false);
        $photo->expects($this->never())
            ->method('checkMediaUpdated');
        $photo->expects($this->once())
            ->method('insertMedia')
            ->with(
                $this->equalTo($mockPhotoModel),
                $this->equalTo($media),
                $this->equalTo($mockTimezone)
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

        $result = $reflectedProcessMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $media,
        ]);

        $this->assertTrue($result);
    }

    public function testProcessMediaChecksMediaUpdated()
    {
        $media = (object) [
            'id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMediaExists',
                'checkMediaUpdated',
                'insertMedia',
                'updateMedia',
            ])
            ->getMock();
        $photo->method('checkMediaExists')
            ->willReturn(true);
        $photo->expects($this->once())
            ->method('checkMediaUpdated')
            ->with(
                $this->equalTo($mockPhotoModel),
                $this->equalTo($media->id),
                $this->equalTo($media)
            )
            ->willReturn(false);
        $photo->expects($this->never())
            ->method('updateMedia');

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $reflectedProcessMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $media,
        ]);
    }

    public function testProcessMediaUpdatesIfMediaHasBeenUpdated()
    {
        $media = (object) [
            'id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('Updated media: 123')
            );

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMediaExists',
                'checkMediaUpdated',
                'insertMedia',
                'updateMedia',
            ])
            ->getMock();
        $photo->method('checkMediaExists')
            ->willReturn(true);
        $photo->method('checkMediaUpdated')
            ->willReturn(true);
        $photo->expects($this->once())
            ->method('updateMedia')
            ->with(
                $this->equalTo($mockPhotoModel),
                $this->equalTo($media->id),
                $this->equalTo($media)
            );

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $result = $reflectedProcessMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $media,
        ]);

        $this->assertTrue($result);
    }

    public function testProcessMediaReturnsFalseIfNoChange()
    {
        $media = (object) [
            'id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);

        $mockLogger = $this->createMock(Logger::class);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMediaExists',
                'checkMediaUpdated',
                'insertMedia',
                'updateMedia',
            ])
            ->getMock();
        $photo->method('checkMediaExists')
            ->willReturn(true);
        $photo->method('checkMediaUpdated')
            ->willReturn(false);

        $reflectedPhoto = new ReflectionClass(Photo::class);

        $reflectedLoggerProperty = $reflectedPhoto->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($photo, $mockLogger);

        $reflectedProcessMediaMethod = $reflectedPhoto->getMethod('processMedia');
        $reflectedProcessMediaMethod->setAccessible(true);

        $result = $reflectedProcessMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $media,
        ]);

        $this->assertFalse($result);
    }

    public function testCheckMediaExistsPullsFromPhotoModel()
    {
        $mediaId = '123';

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->expects($this->once())
            ->method('getMediaByMediaId')
            ->with(
                $this->equalTo($mediaId)
            );

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedCheckMediaExistsMethod = $reflectedPhoto->getMethod('checkMediaExists');
        $reflectedCheckMediaExistsMethod->setAccessible(true);

        $reflectedCheckMediaExistsMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mediaId,
        ]);
    }

    public function testCheckMediaExistsReturnsTrueIfRecordExists()
    {
        $media = [
            'id' => '123',
            'media_id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('getMediaByMediaId')
            ->willReturn($media);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedCheckMediaExistsMethod = $reflectedPhoto->getMethod('checkMediaExists');
        $reflectedCheckMediaExistsMethod->setAccessible(true);

        $result = $reflectedCheckMediaExistsMethod->invokeArgs($photo, [
            $mockPhotoModel,
            '',
        ]);

        $this->assertTrue($result);
    }

    public function testCheckMediaExistsReturnsFalsesIfRecordNotExists()
    {
        $media = false;

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('getMediaByMediaId')
            ->willReturn($media);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedCheckMediaExistsMethod = $reflectedPhoto->getMethod('checkMediaExists');
        $reflectedCheckMediaExistsMethod->setAccessible(true);

        $result = $reflectedCheckMediaExistsMethod->invokeArgs($photo, [
            $mockPhotoModel,
            '',
        ]);

        $this->assertFalse($result);
    }

    public function testInsertMediaCastsDateToDateTime()
    {
        $date = '1467288000';
        $dateTime = new DateTime("@{$date}");

        $mockMedia = (object) [
            'id' => '123',
            'created_time' => $date,
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->expects($this->once())
            ->method('insertMedia')
            ->with(
                $this->anything(),
                $this->equalTo($dateTime),
                $this->anything()
            )
            ->willReturn(true);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $reflectedInsertMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mockMedia,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertMediaSetsDateTimeZone()
    {
        $date = '1467288000';
        $timezone = 'America/Phoenix'; // always +700, no DST

        $mockMedia = (object) [
            'id' => '123',
            'created_time' => $date,
        ];

        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime("@{$date}");
        $dateTime->setTimezone($dateTimeZone);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->expects($this->once())
            ->method('insertMedia')
            ->with(
                $this->anything(),
                $this->callback(function ($param) use ($dateTime) {
                    return $param->getTimeZone()->getName() == $dateTime->getTimeZone()->getName();
                }),
                $this->anything()
            )
            ->willReturn(true);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $reflectedInsertMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mockMedia,
            $dateTimeZone,
        ]);
    }

    public function testInsertMediaSendsParamsToPhotoModel()
    {
        $date = '1467288000';
        $dateTime = new DateTime("@{$date}");

        $id = '123';

        $mockMedia = (object) [
            'id' => $id,
            'created_time' => $date,
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->expects($this->once())
            ->method('insertMedia')
            ->with(
                $this->equalTo($id),
                $this->equalTo($dateTime),
                $this->equalTo(json_encode($mockMedia))
            )
            ->willReturn(true);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $reflectedInsertMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mockMedia,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to insert media
     */
    public function testInsertMediaThrowsExceptionIfModelThrows()
    {
        $exception = new Exception('Failed to insert media');

        $mockMedia = (object) [
            'id' => '123',
            'created_time' => '1467288000',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('insertMedia')
            ->will($this->throwException($exception));

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $reflectedInsertMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mockMedia,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to insert new media: 123
     */
    public function testInsertMediaThrowsExceptionIfInsertFails()
    {
        $id = '123';

        $mockMedia = (object) [
            'id' => $id,
            'created_time' => '1467288000',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('insertMedia')
            ->willReturn(false);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $reflectedInsertMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mockMedia,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertMediaReturnsTrueIfInsertSucceeds()
    {
        $mockMedia = (object) [
            'id' => '123',
            'created_time' => '1467288000',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('insertMedia')
            ->willReturn(true);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedInsertMediaMethod = $reflectedPhoto->getMethod('insertMedia');
        $reflectedInsertMediaMethod->setAccessible(true);

        $result = $reflectedInsertMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mockMedia,
            $mockDateTimeZone,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckMediaUpdatedPullsFromPhotoModel()
    {
        $mediaId = '123';
        $media = (object) [
            'likes' => (object) [
                'count' => 1,
            ],
            'comments' => (object) [
                'count' => 1,
            ],
        ];

        $metadata = (object) [
            'likes' => (object) [
                'count' => 1,
            ],
            'comments' => (object) [
                'count' => 1,
            ],
        ];
        $jsonMetadata = json_encode($metadata);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->expects($this->once())
            ->method('getMediaByMediaId')
            ->with(
                $this->equalTo($mediaId)
            )
            ->willReturn([ 'metadata' => $jsonMetadata ]);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedCheckMediaUpdatedMethod = $reflectedPhoto->getMethod('checkMediaUpdated');
        $reflectedCheckMediaUpdatedMethod->setAccessible(true);

        $reflectedCheckMediaUpdatedMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mediaId,
            $media,
        ]);
    }

    public function testCheckMediaUpdatedReturnsTrueIfLikesUpdated()
    {
        $media = (object) [
            'likes' => (object) [
                'count' => 2,
            ],
            'comments' => (object) [
                'count' => 1,
            ],
        ];

        $metadata = (object) [
            'likes' => (object) [
                'count' => 1,
            ],
            'comments' => (object) [
                'count' => 1,
            ],
        ];
        $jsonMetadata = json_encode($metadata);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('getMediaByMediaId')
            ->willReturn([ 'metadata' => $jsonMetadata ]);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedCheckMediaUpdatedMethod = $reflectedPhoto->getMethod('checkMediaUpdated');
        $reflectedCheckMediaUpdatedMethod->setAccessible(true);

        $result = $reflectedCheckMediaUpdatedMethod->invokeArgs($photo, [
            $mockPhotoModel,
            '',
            $media,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckMediaUpdatedReturnsTrueIfCommentsUpdated()
    {
        $media = (object) [
            'likes' => (object) [
                'count' => 1,
            ],
            'comments' => (object) [
                'count' => 2,
            ],
        ];

        $metadata = (object) [
            'likes' => (object) [
                'count' => 1,
            ],
            'comments' => (object) [
                'count' => 1,
            ],
        ];
        $jsonMetadata = json_encode($metadata);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('getMediaByMediaId')
            ->willReturn([ 'metadata' => $jsonMetadata ]);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedCheckMediaUpdatedMethod = $reflectedPhoto->getMethod('checkMediaUpdated');
        $reflectedCheckMediaUpdatedMethod->setAccessible(true);

        $result = $reflectedCheckMediaUpdatedMethod->invokeArgs($photo, [
            $mockPhotoModel,
            '',
            $media,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckMediaUpdatedReturnsFalseIfNotUpdated()
    {
        $media = (object) [
            'likes' => (object) [
                'count' => 1,
            ],
            'comments' => (object) [
                'count' => 1,
            ],
        ];

        $metadata = (object) [
            'likes' => (object) [
                'count' => 1,
            ],
            'comments' => (object) [
                'count' => 1,
            ],
        ];
        $jsonMetadata = json_encode($metadata);

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('getMediaByMediaId')
            ->willReturn([ 'metadata' => $jsonMetadata ]);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedCheckMediaUpdatedMethod = $reflectedPhoto->getMethod('checkMediaUpdated');
        $reflectedCheckMediaUpdatedMethod->setAccessible(true);

        $result = $reflectedCheckMediaUpdatedMethod->invokeArgs($photo, [
            $mockPhotoModel,
            '',
            $media,
        ]);

        $this->assertFalse($result);
    }

    public function testUpdateMediaSendsValuesToModel()
    {
        $mediaId = '123';
        $media = (object) [
            'id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->expects($this->once())
            ->method('updateMedia')
            ->with(
                $this->equalTo($mediaId),
                $this->equalTo(json_encode($media))
            )
            ->willReturn(true);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedUpdateMediaMethod = $reflectedPhoto->getMethod('updateMedia');
        $reflectedUpdateMediaMethod->setAccessible(true);

        $reflectedUpdateMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            $mediaId,
            $media,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to update media: 123
     */
    public function testUpdateMediaThrowsExceptionIfModelFails()
    {
        $media = (object) [
            'id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('updateMedia')
            ->willReturn(false);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedUpdateMediaMethod = $reflectedPhoto->getMethod('updateMedia');
        $reflectedUpdateMediaMethod->setAccessible(true);

        $reflectedUpdateMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            '123',
            $media,
        ]);
    }

    public function testUpdateMediaReturnsTrueIfModelSucceeds()
    {
        $media = (object) [
            'id' => '123',
        ];

        $mockPhotoModel = $this->createMock(PhotoModel::class);
        $mockPhotoModel->method('updateMedia')
            ->willReturn(true);

        $photo = $this->getMockBuilder(Photo::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedPhoto = new ReflectionClass(Photo::class);
        $reflectedUpdateMediaMethod = $reflectedPhoto->getMethod('updateMedia');
        $reflectedUpdateMediaMethod->setAccessible(true);

        $result = $reflectedUpdateMediaMethod->invokeArgs($photo, [
            $mockPhotoModel,
            '',
            $media,
        ]);

        $this->assertTrue($result);
    }
}
