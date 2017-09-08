<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;
use stdclass;

use PHPUnit\Framework\TestCase;

use Abraham\TwitterOAuth\TwitterOAuth as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Twitter as TwitterModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class TwitterTest extends TestCase
{

    public function testIsInstanceOfTwitter()
    {
        $mockContainer = $this->createMock(Container::class);
        $twitter = new Twitter($mockContainer);

        $this->assertInstanceOf(Twitter::class, $twitter);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $twitter = new Twitter($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $twitter);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $twitter = new Twitter($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $twitter);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $twitter = new Twitter($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $twitter);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $twitter = new Twitter($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $twitter);
    }

    public function testRunFetchesTweets()
    {
        $mockConfig = (object) [
            'twitter' => (object) [
                'screenname' => 'user',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'twitterClient', $mockClient ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchTweets',
                'processTweet',
            ])
            ->getMock();
        $twitter->expects($this->once())
            ->method('fetchTweets')
            ->with(
                $this->equalTo($mockClient),
                $this->equalTo($mockConfig->twitter->screenname),
                $this->anything()
            )
            ->willReturn([]);

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedContainerProperty = $reflectedTwitter->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($twitter, $mockContainer);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $twitter->run();
    }

    public function testRunLogsThrownExceptionFromFetchTweets()
    {
        $mockExceptionMessage = 'Failed to fetch tweets';
        $mockException = new Exception($mockExceptionMessage);

        $mockConfig = (object) [
            'twitter' => (object) [
                'screenname' => 'user',
            ],
        ];

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'twitterClient', $mockClient ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchTweets',
                'processTweet',
            ])
            ->getMock();
        $twitter->method('fetchTweets')
            ->will($this->throwException($mockException));
        $twitter->expects($this->never())
            ->method('processTweet');

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedContainerProperty = $reflectedTwitter->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($twitter, $mockContainer);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $twitter->run();
    }

    public function testRunProcessesEachTweet()
    {
        $tweets = [
            (object) [
                'id_str' => '123',
            ],
            (object) [
                'id_str' => '456',
            ],
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'twitter' => (object) [
                'screenname' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'twitterClient', $mockClient ],
                [ 'twitterModel', $mockTwitterModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchTweets',
                'processTweet',
            ])
            ->getMock();
        $twitter->method('fetchTweets')
            ->will($this->onConsecutiveCalls($tweets, []));
        $twitter->expects($this->exactly(count($tweets)))
            ->method('processTweet')
            ->withConsecutive(
                [ $mockTwitterModel, $tweets[0] ],
                [ $mockTwitterModel, $tweets[1] ]
            );

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedContainerProperty = $reflectedTwitter->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($twitter, $mockContainer);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $twitter->run();
    }

    public function testRunLogsThrownExceptionFromProcessTweet()
    {
        $mockExceptionMessage = 'Failed to insert tweet';
        $mockException = new Exception($mockExceptionMessage);

        $tweets = [
            (object) [
                'id_str' => '123',
            ],
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'twitter' => (object) [
                'screenname' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'twitterClient', $mockClient ],
                [ 'twitterModel', $mockTwitterModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchTweets',
                'processTweet',
            ])
            ->getMock();
        $twitter->method('fetchTweets')
            ->willReturn($tweets);
        $twitter->method('processTweet')
            ->will($this->throwException($mockException));

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedContainerProperty = $reflectedTwitter->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($twitter, $mockContainer);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $twitter->run();
    }

    public function testRunMakesSingleRequestIfNoTweets()
    {
        $tweets = [];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'twitter' => (object) [
                'screenname' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'twitterClient', $mockClient ],
                [ 'twitterModel', $mockTwitterModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchTweets',
                'processTweet',
            ])
            ->getMock();
        $twitter->expects($this->once())
            ->method('fetchTweets')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->equalTo(null)
            )
            ->willReturn($tweets);
        $twitter->expects($this->never())
            ->method('processTweet');

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedContainerProperty = $reflectedTwitter->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($twitter, $mockContainer);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $twitter->run();
    }

    public function testRunMakesRepeatRequestUsingCursor()
    {
        $tweets = [
            (object) [
                'id_str' => '123',
            ],
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockConfig = (object) [
            'twitter' => (object) [
                'screenname' => 'user',
            ],
        ];

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'config', $mockConfig ],
                [ 'twitterClient', $mockClient ],
                [ 'twitterModel', $mockTwitterModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Processing page 1 of tweets'));
        $mockLogger->expects($this->never())
            ->method('error');

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchTweets',
                'processTweet',
            ])
            ->getMock();
        $twitter->expects($this->exactly(2))
            ->method('fetchTweets')
            ->withConsecutive(
                [ $this->anything(), $this->anything(), $this->equalTo(null) ],
                [ $this->anything(), $this->anything(), $this->equalTo($tweets[0]->id_str) ]
            )
            ->will($this->onConsecutiveCalls($tweets, []));

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedContainerProperty = $reflectedTwitter->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($twitter, $mockContainer);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $twitter->run();
    }

    public function testFetchTweetsPullsFromClient()
    {
        $screenname = 'user';

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('statuses/user_timeline'),
                $this->equalTo([
                    'screen_name' => $screenname,
                    'count' => 200,
                    'trim_user' => true,
                ])
            );
        $mockClient->expects($this->once())
            ->method('getLastHttpCode')
            ->willReturn(200);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedFetchTweetsMethod = $reflectedTwitter->getMethod('fetchTweets');
        $reflectedFetchTweetsMethod->setAccessible(true);

        $reflectedFetchTweetsMethod->invokeArgs($twitter, [
            $mockClient,
            $screenname,
            null,
        ]);
    }

    public function testFetchTweetsPassesMaxIdToClient()
    {
        $screenname = 'user';
        $maxId = '123';

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('statuses/user_timeline'),
                $this->equalTo([
                    'screen_name' => $screenname,
                    'count' => 200,
                    'trim_user' => true,
                    'max_id' => $maxId,
                ])
            );
        $mockClient->expects($this->once())
            ->method('getLastHttpCode')
            ->willReturn(200);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedFetchTweetsMethod = $reflectedTwitter->getMethod('fetchTweets');
        $reflectedFetchTweetsMethod->setAccessible(true);

        $reflectedFetchTweetsMethod->invokeArgs($twitter, [
            $mockClient,
            $screenname,
            $maxId,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error with fetching tweets: 400
     */
    public function testFetchTweetsThrowsExceptionOnNon200Status()
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('getLastHttpCode')
            ->willReturn(400);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedFetchTweetsMethod = $reflectedTwitter->getMethod('fetchTweets');
        $reflectedFetchTweetsMethod->setAccessible(true);

        $reflectedFetchTweetsMethod->invokeArgs($twitter, [
            $mockClient,
            '',
            null,
        ]);
    }

    public function testFetchTweetsReturnsTweets()
    {
        $tweets = [
            'id' => 1,
        ];

        $mockClient = $this->createMock(Client::class);
        $mockClient->method('get')
            ->willReturn($tweets);
        $mockClient->method('getLastHttpCode')
            ->willReturn(200);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedFetchTweetsMethod = $reflectedTwitter->getMethod('fetchTweets');
        $reflectedFetchTweetsMethod->setAccessible(true);

        $result = $reflectedFetchTweetsMethod->invokeArgs($twitter, [
            $mockClient,
            '',
            null,
        ]);

        $this->assertEquals($tweets, $result);
    }

    public function testProcessTweetChecksTweetExists()
    {
        $tweet = (object) [
            'id_str' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkTweetExists',
                'checkTweetUpdated',
                'insertTweet',
                'updateTweet',
            ])
            ->getMock();
        $twitter->expects($this->once())
            ->method('checkTweetExists')
            ->with(
                $this->equalTo($mockTwitterModel),
                $this->equalTo($tweet->id_str)
            )
            ->willReturn(true);
        $twitter->expects($this->never())
            ->method('insertTweet');

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $reflectedProcessTweetMethod = $reflectedTwitter->getMethod('processTweet');
        $reflectedProcessTweetMethod->setAccessible(true);

        $reflectedProcessTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $tweet,
        ]);
    }

    public function testProcessTweetInsertsIfTweetDoesNotExist()
    {
        $tweet = (object) [
            'id_str' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
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
                $this->equalTo('Inserted new tweet: 123')
            );

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkTweetExists',
                'checkTweetUpdated',
                'insertTweet',
                'updateTweet',
            ])
            ->getMock();
        $twitter->method('checkTweetExists')
            ->willReturn(false);
        $twitter->expects($this->never())
            ->method('checkTweetUpdated');
        $twitter->expects($this->once())
            ->method('insertTweet')
            ->with(
                $this->equalTo($mockTwitterModel),
                $this->equalTo($tweet),
                $this->equalTo($mockTimezone)
            );

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedContainerProperty = $reflectedTwitter->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($twitter, $mockContainer);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $reflectedProcessTweetMethod = $reflectedTwitter->getMethod('processTweet');
        $reflectedProcessTweetMethod->setAccessible(true);

        $result = $reflectedProcessTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $tweet,
        ]);

        $this->assertTrue($result);
    }

    public function testProcessTweetChecksTweetUpdated()
    {
        $tweet = (object) [
            'id_str' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkTweetExists',
                'checkTweetUpdated',
                'insertTweet',
                'updateTweet',
            ])
            ->getMock();
        $twitter->method('checkTweetExists')
            ->willReturn(true);
        $twitter->expects($this->once())
            ->method('checkTweetUpdated')
            ->with(
                $this->equalTo($mockTwitterModel),
                $this->equalTo($tweet->id_str),
                $this->equalTo($tweet)
            )
            ->willReturn(false);
        $twitter->expects($this->never())
            ->method('updateTweet');

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $reflectedProcessTweetMethod = $reflectedTwitter->getMethod('processTweet');
        $reflectedProcessTweetMethod->setAccessible(true);

        $reflectedProcessTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $tweet,
        ]);
    }

    public function testProcessTweetUpdatesIfTweetHasBeenUpdated()
    {
        $tweet = (object) [
            'id_str' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('Updated tweet: 123')
            );

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkTweetExists',
                'checkTweetUpdated',
                'insertTweet',
                'updateTweet',
            ])
            ->getMock();
        $twitter->method('checkTweetExists')
            ->willReturn(true);
        $twitter->method('checkTweetUpdated')
            ->willReturn(true);
        $twitter->expects($this->once())
            ->method('updateTweet')
            ->with(
                $this->equalTo($mockTwitterModel),
                $this->equalTo($tweet->id_str),
                $this->equalTo($tweet)
            );

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $reflectedProcessTweetMethod = $reflectedTwitter->getMethod('processTweet');
        $reflectedProcessTweetMethod->setAccessible(true);

        $result = $reflectedProcessTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $tweet,
        ]);

        $this->assertTrue($result);
    }

    public function testProcessTweetReturnsFalseIfNoChange()
    {
        $tweet = (object) [
            'id_str' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);

        $mockLogger = $this->createMock(Logger::class);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkTweetExists',
                'checkTweetUpdated',
                'insertTweet',
                'updateTweet',
            ])
            ->getMock();
        $twitter->method('checkTweetExists')
            ->willReturn(true);
        $twitter->method('checkTweetUpdated')
            ->willReturn(false);

        $reflectedTwitter = new ReflectionClass(Twitter::class);

        $reflectedLoggerProperty = $reflectedTwitter->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($twitter, $mockLogger);

        $reflectedProcessTweetMethod = $reflectedTwitter->getMethod('processTweet');
        $reflectedProcessTweetMethod->setAccessible(true);

        $result = $reflectedProcessTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $tweet,
        ]);

        $this->assertFalse($result);
    }

    public function testCheckTweetExistsPullsFromTwitterModel()
    {
        $tweetId = '123';

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->expects($this->once())
            ->method('getTweetByTweetId')
            ->with(
                $this->equalTo($tweetId)
            );

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedCheckTweetExistsMethod = $reflectedTwitter->getMethod('checkTweetExists');
        $reflectedCheckTweetExistsMethod->setAccessible(true);

        $reflectedCheckTweetExistsMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $tweetId,
        ]);
    }

    public function testCheckTweetExistsReturnsTrueIfRecordExists()
    {
        $tweet = [
            'id' => '123',
            'tweet_id' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('getTweetByTweetId')
            ->willReturn($tweet);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedCheckTweetExistsMethod = $reflectedTwitter->getMethod('checkTweetExists');
        $reflectedCheckTweetExistsMethod->setAccessible(true);

        $result = $reflectedCheckTweetExistsMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            '',
        ]);

        $this->assertTrue($result);
    }

    public function testCheckTweetExistsReturnsFalsesIfRecordNotExists()
    {
        $tweet = false;

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('getTweetByTweetId')
            ->willReturn($tweet);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedCheckTweetExistsMethod = $reflectedTwitter->getMethod('checkTweetExists');
        $reflectedCheckTweetExistsMethod->setAccessible(true);

        $result = $reflectedCheckTweetExistsMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            '',
        ]);

        $this->assertFalse($result);
    }

    public function testInsertTweetCastsDateToDateTime()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $mockTweet = (object) [
            'id_str' => '123',
            'created_at' => $date,
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->expects($this->once())
            ->method('insertTweet')
            ->with(
                $this->anything(),
                $this->equalTo($dateTime),
                $this->anything()
            )
            ->willReturn(true);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedInsertTweetMethod = $reflectedTwitter->getMethod('insertTweet');
        $reflectedInsertTweetMethod->setAccessible(true);

        $reflectedInsertTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $mockTweet,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertTweetSetsDateTimeZone()
    {
        $date = '2016-06-30 12:00:00 +000';
        $timezone = 'America/Phoenix'; // always +700, no DST

        $mockTweet = (object) [
            'id_str' => '123',
            'created_at' => $date,
        ];

        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime($date);
        $dateTime->setTimezone($dateTimeZone);

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->expects($this->once())
            ->method('insertTweet')
            ->with(
                $this->anything(),
                $this->callback(function ($param) use ($dateTime) {
                    return $param->getTimeZone()->getName() == $dateTime->getTimeZone()->getName();
                }),
                $this->anything()
            )
            ->willReturn(true);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedInsertTweetMethod = $reflectedTwitter->getMethod('insertTweet');
        $reflectedInsertTweetMethod->setAccessible(true);

        $reflectedInsertTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $mockTweet,
            $dateTimeZone,
        ]);
    }

    public function testInsertTweetSendsParamsToTwitterModel()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $id = '123';

        $mockTweet = (object) [
            'id_str' => $id,
            'created_at' => $date,
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->expects($this->once())
            ->method('insertTweet')
            ->with(
                $this->equalTo($id),
                $this->equalTo($dateTime),
                $this->equalTo(json_encode($mockTweet))
            )
            ->willReturn(true);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedInsertTweetMethod = $reflectedTwitter->getMethod('insertTweet');
        $reflectedInsertTweetMethod->setAccessible(true);

        $reflectedInsertTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $mockTweet,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to insert tweet
     */
    public function testInsertTweetThrowsExceptionIfModelThrows()
    {
        $exception = new Exception('Failed to insert tweet');

        $mockTweet = (object) [
            'id_str' => '123',
            'created_at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('insertTweet')
            ->will($this->throwException($exception));

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedInsertTweetMethod = $reflectedTwitter->getMethod('insertTweet');
        $reflectedInsertTweetMethod->setAccessible(true);

        $reflectedInsertTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $mockTweet,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to insert new tweet: 123
     */
    public function testInsertTweetThrowsExceptionIfInsertFails()
    {
        $id = '123';

        $mockTweet = (object) [
            'id_str' => $id,
            'created_at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('insertTweet')
            ->willReturn(false);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedInsertTweetMethod = $reflectedTwitter->getMethod('insertTweet');
        $reflectedInsertTweetMethod->setAccessible(true);

        $reflectedInsertTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $mockTweet,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertTweetReturnsTrueIfInsertSucceeds()
    {
        $mockTweet = (object) [
            'id_str' => '123',
            'created_at' => '2016-06-30 12:00:00',
        ];

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('insertTweet')
            ->willReturn(true);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedInsertTweetMethod = $reflectedTwitter->getMethod('insertTweet');
        $reflectedInsertTweetMethod->setAccessible(true);

        $result = $reflectedInsertTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $mockTweet,
            $mockDateTimeZone,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckTweetUpdatedPullsFromTwitterModel()
    {
        $tweetId = '123';
        $tweet = new stdclass();

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->expects($this->once())
            ->method('getTweetByTweetId')
            ->with(
                $this->equalTo($tweetId)
            );

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedCheckTweetUpdatedMethod = $reflectedTwitter->getMethod('checkTweetUpdated');
        $reflectedCheckTweetUpdatedMethod->setAccessible(true);

        $reflectedCheckTweetUpdatedMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $tweetId,
            $tweet,
        ]);
    }

    public function testCheckTweetUpdatedReturnsTrueIfUpdated()
    {
        $oldTweet = (object) [
            'id' => '123',
        ];
        $newTweet = (object) [
            'id' => '456',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('getTweetByTweetId')
            ->willReturn([
                'metadata' => json_encode($oldTweet),
            ]);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedCheckTweetUpdatedMethod = $reflectedTwitter->getMethod('checkTweetUpdated');
        $reflectedCheckTweetUpdatedMethod->setAccessible(true);

        $result = $reflectedCheckTweetUpdatedMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            '',
            $newTweet,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckTweetUpdatedReturnsFalseIfNotUpdated()
    {
        $tweet = (object) [
            'id' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('getTweetByTweetId')
            ->willReturn([
                'metadata' => json_encode($tweet),
            ]);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedCheckTweetUpdatedMethod = $reflectedTwitter->getMethod('checkTweetUpdated');
        $reflectedCheckTweetUpdatedMethod->setAccessible(true);

        $result = $reflectedCheckTweetUpdatedMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            '',
            $tweet,
        ]);

        $this->assertFalse($result);
    }

    public function testUpdateTweetSendsValuesToModel()
    {
        $tweetId = '123';
        $tweet = (object) [
            'id' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->expects($this->once())
            ->method('updateTweet')
            ->with(
                $this->equalTo($tweetId),
                $this->equalTo(json_encode($tweet))
            )
            ->willReturn(true);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedUpdateTweetMethod = $reflectedTwitter->getMethod('updateTweet');
        $reflectedUpdateTweetMethod->setAccessible(true);

        $reflectedUpdateTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            $tweetId,
            $tweet,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to update tweet: 123
     */
    public function testUpdateTweetThrowsExceptionIfModelFails()
    {
        $tweet = (object) [
            'id' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('updateTweet')
            ->willReturn(false);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedUpdateTweetMethod = $reflectedTwitter->getMethod('updateTweet');
        $reflectedUpdateTweetMethod->setAccessible(true);

        $reflectedUpdateTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            '123',
            $tweet,
        ]);
    }

    public function testUpdateTweetReturnsTrueIfModelSucceeds()
    {
        $tweet = (object) [
            'id' => '123',
        ];

        $mockTwitterModel = $this->createMock(TwitterModel::class);
        $mockTwitterModel->method('updateTweet')
            ->willReturn(true);

        $twitter = $this->getMockBuilder(Twitter::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedTwitter = new ReflectionClass(Twitter::class);
        $reflectedUpdateTweetMethod = $reflectedTwitter->getMethod('updateTweet');
        $reflectedUpdateTweetMethod->setAccessible(true);

        $result = $reflectedUpdateTweetMethod->invokeArgs($twitter, [
            $mockTwitterModel,
            '',
            $tweet,
        ]);

        $this->assertTrue($result);
    }
}
