<?php

namespace Jacobemerick\LifestreamService\Cron\Fetch;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;
use SimpleXMLElement;

use PHPUnit\Framework\TestCase;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\BlogComment as BlogCommentModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class BlogCommentTest extends TestCase
{

    public function testIsInstanceOfBlogComment()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new BlogComment($mockContainer);

        $this->assertInstanceOf(BlogComment::class, $cron);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new BlogComment($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $cron);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new BlogComment($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $cron);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new BlogComment($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new BlogComment($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $cron);
    }

    public function testRunFetchesComments()
    {
        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkCommentExists',
                'fetchComments',
                'insertComment',
            ])
            ->getMock();
        $blogComment->expects($this->never())
            ->method('checkCommentExists');
        $blogComment->expects($this->once())
            ->method('fetchComments')
            ->with($mockClient)
            ->willReturn([]);
        $blogComment->expects($this->never())
            ->method('insertComment');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }
 
    public function testRunLogsThrownExceptionFromFetchComments()
    {
        $mockExceptionMessage = 'Failed to fetch comments';
        $mockException = new Exception($mockExceptionMessage);

        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkCommentExists',
                'fetchComments',
                'insertComment',
            ])
            ->getMock();
        $blogComment->expects($this->never())
            ->method('checkCommentExists');
        $blogComment->method('fetchComments')
            ->will($this->throwException($mockException));
        $blogComment->expects($this->never())
            ->method('insertComment');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunChecksIfEachCommentExists()
    {
        $comments = [
            new SimpleXMLElement('<rss><guid>http://site.com/some-post#comment-123</guid></rss>'),
            new SimpleXMLElement('<rss><guid>http://site.com/some-post#comment-456</guid></rss>'),
        ];

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
                [ 'blogCommentModel', $mockBlogCommentModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkCommentExists',
                'fetchComments',
                'insertComment',
            ])
            ->getMock();
        $blogComment->expects($this->exactly(count($comments)))
            ->method('checkCommentExists')
            ->withConsecutive(
                [ $mockBlogCommentModel, $comments[0]->guid ],
                [ $mockBlogCommentModel, $comments[1]->guid ]
            )
            ->willReturn(true);
        $blogComment->method('fetchComments')
            ->with($mockClient)
            ->willReturn($comments);
        $blogComment->expects($this->never())
            ->method('insertComment');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunPassesOntoInsertIfCommentNotExists()
    {
        $comments = [
            new SimpleXMLElement('<rss><guid>http://site.com/some-post#comment-123</guid></rss>'),
        ];

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
                [ 'blogCommentModel', $mockBlogCommentModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkCommentExists',
                'fetchComments',
                'insertComment',
            ])
            ->getMock();
        $blogComment->expects($this->exactly(count($comments)))
            ->method('checkCommentExists')
            ->withConsecutive(
                [ $mockBlogCommentModel, $comments[0]->guid ]
            )
            ->willReturn(false);
        $blogComment->method('fetchComments')
            ->willReturn($comments);
        $blogComment->expects($this->exactly(count($comments)))
            ->method('insertComment')
            ->withConsecutive(
                [ $mockBlogCommentModel, $comments[0], $mockTimezone ]
            );

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunLogsThrownExceptionFromInsertComment()
    {
        $mockExceptionMessage = 'Failed to insert comment';
        $mockException = new Exception($mockExceptionMessage);

        $comments = [
            new SimpleXMLElement('<rss><guid>http://site.com/some-post#comment-123</guid></rss>'),
        ];

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
                [ 'blogCommentModel', $mockBlogCommentModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($mockExceptionMessage);
        $mockLogger->expects($this->never())
            ->method('debug');

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkCommentExists',
                'fetchComments',
                'insertComment',
            ])
            ->getMock();
        $blogComment->expects($this->exactly(count($comments)))
            ->method('checkCommentExists')
            ->withConsecutive(
                [ $mockBlogCommentModel, $comments[0]->guid ]
            )
            ->willReturn(false);
        $blogComment->method('fetchComments')
            ->willReturn($comments);
        $blogComment->method('insertComment')
            ->will($this->throwException($mockException));

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunLogsInsertedCommentIfSuccessful()
    {
        $comments = [
            new SimpleXMLElement('<rss><guid>http://site.com/some-post#comment-123</guid></rss>'),
        ];

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
                [ 'blogCommentModel', $mockBlogCommentModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('error');
        $mockLogger->expects($this->exactly($this->count($comments)))
            ->method('debug')
            ->with(
                $this->equalTo('Inserted new blog comment: http://site.com/some-post#comment-123')
            );

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkCommentExists',
                'fetchComments',
                'insertComment',
            ])
            ->getMock();
        $blogComment->expects($this->exactly(count($comments)))
            ->method('checkCommentExists')
            ->withConsecutive(
                [ $mockBlogCommentModel, $comments[0]->guid ]
            )
            ->willReturn(false);
        $blogComment->method('fetchComments')
            ->willReturn($comments);
        $blogComment->expects($this->exactly(count($comments)))
            ->method('insertComment')
            ->withConsecutive(
                [ $mockBlogCommentModel, $comments[0], $mockTimezone ]
            );

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testFetchCommentsPullsFromClient()
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getBody')
            ->willReturn('<rss><channel><item /></channel></rss>');
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('rss-comments.xml')
            )
            ->willReturn($mockResponse);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedFetchCommentsMethod = $reflectedBlogComment->getMethod('fetchComments');
        $reflectedFetchCommentsMethod->setAccessible(true);

        $reflectedFetchCommentsMethod->invokeArgs($blogComment, [
            $mockClient,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to fetch rss feed: 400
     */
    public function testFetchCommentsThrowsExceptionOnNon200Status()
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->never())
            ->method('getBody');
        $mockResponse->method('getStatusCode')
            ->willReturn(400);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('rss-comments.xml')
            )
            ->willReturn($mockResponse);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedFetchCommentsMethod = $reflectedBlogComment->getMethod('fetchComments');
        $reflectedFetchCommentsMethod->setAccessible(true);

        $reflectedFetchCommentsMethod->invokeArgs($blogComment, [
            $mockClient,
        ]);
    }

    public function testFetchCommentsReturnsItems()
    {
        $items = '<item><id>123</id></item>';
        $xmlItems = new SimpleXMLElement($items);

        $rss = "<rss><channel>{$items}</channel></rss>";

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($rss);
        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('rss-comments.xml')
            )
            ->willReturn($mockResponse);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedFetchCommentsMethod = $reflectedBlogComment->getMethod('fetchComments');
        $reflectedFetchCommentsMethod->setAccessible(true);

        $result = $reflectedFetchCommentsMethod->invokeArgs($blogComment, [
            $mockClient,
        ]);

        $this->assertEquals($xmlItems, $result);
    }

    public function testCheckCommentExistsPullsFromBlogCommentModel()
    {
        $permalink = 'http://site.com/some-post#comment-123';

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->expects($this->once())
            ->method('getCommentByPermalink')
            ->with(
                $this->equalTo($permalink)
            );

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedCheckCommentExistsMethod = $reflectedBlogComment->getMethod('checkCommentExists');
        $reflectedCheckCommentExistsMethod->setAccessible(true);

        $reflectedCheckCommentExistsMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            $permalink,
        ]);
    }

    public function testCheckCommentExistsReturnsTrueIfRecordExists()
    {
        $comment = [
            'id' => '123',
            'permalink' => 'http://site.com/some-post#comment-123',
        ];

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->method('getCommentByPermalink')
            ->willReturn($comment);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedCheckCommentExistsMethod = $reflectedBlogComment->getMethod('checkCommentExists');
        $reflectedCheckCommentExistsMethod->setAccessible(true);

        $result = $reflectedCheckCommentExistsMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            '',
        ]);

        $this->assertTrue($result);
    }

    public function testCheckCommentExistsReturnsFalsesIfRecordNotExists()
    {
        $comment = false;

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->method('getCommentByPermalink')
            ->willReturn($comment);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedCheckCommentExistsMethod = $reflectedBlogComment->getMethod('checkCommentExists');
        $reflectedCheckCommentExistsMethod->setAccessible(true);

        $result = $reflectedCheckCommentExistsMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            '',
        ]);

        $this->assertFalse($result);
    }

    public function testInsertCommentCastsDateToDateTime()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $mockComment = "<item><guid /><pubDate>{$date}</pubDate></item>";
        $mockComment = new SimpleXMLElement($mockComment);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->expects($this->once())
            ->method('insertComment')
            ->with(
                $this->anything(),
                $this->equalTo($dateTime),
                $this->anything()
            )
            ->willReturn(true);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedInsertCommentMethod = $reflectedBlogComment->getMethod('insertComment');
        $reflectedInsertCommentMethod->setAccessible(true);

        $reflectedInsertCommentMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            $mockComment,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertCommentSetsDateTimeZone()
    {
        $date = '2016-06-30 12:00:00 +000';
        $timezone = 'America/Phoenix'; // always +700, no DST

        $mockComment = "<item><guid /><pubDate>{$date}</pubDate></item>";
        $mockComment = new SimpleXMLElement($mockComment);

        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime($date);
        $dateTime->setTimezone($dateTimeZone);

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->expects($this->once())
            ->method('insertComment')
            ->with(
                $this->anything(),
                $this->callback(function ($param) use ($dateTime) {
                    return $param->getTimeZone()->getName() == $dateTime->getTimeZone()->getName();
                }),
                $this->anything()
            )
            ->willReturn(true);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedInsertCommentMethod = $reflectedBlogComment->getMethod('insertComment');
        $reflectedInsertCommentMethod->setAccessible(true);

        $reflectedInsertCommentMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            $mockComment,
            $dateTimeZone,
        ]);
    }

    public function testInsertCommentSendsParamsToBlogCommentModel()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $permalink = 'http://site.com/some-post#comment-123';

        $comment = "<item><guid>{$permalink}</guid><pubDate>{$date}</pubDate></item>";
        $comment = new SimpleXMLElement($comment);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->expects($this->once())
            ->method('insertComment')
            ->with(
                $this->equalTo($permalink),
                $this->equalTo($dateTime),
                $this->equalTo(json_encode($comment))
            )
            ->willReturn(true);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedInsertCommentMethod = $reflectedBlogComment->getMethod('insertComment');
        $reflectedInsertCommentMethod->setAccessible(true);

        $reflectedInsertCommentMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            $comment,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to insert comment
     */
    public function testInsertCommentThrowsExceptionIfModelThrows()
    {
        $exception = new Exception('Failed to insert comment');

        $mockComment = "<item><guid /><pubDate>2016-06-30 12:00:00</pubDate></item>";
        $mockComment = new SimpleXMLElement($mockComment);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->method('insertComment')
            ->will($this->throwException($exception));

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedInsertCommentMethod = $reflectedBlogComment->getMethod('insertComment');
        $reflectedInsertCommentMethod->setAccessible(true);

        $reflectedInsertCommentMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            $mockComment,
            $mockDateTimeZone,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to insert new comment: http://site.com/some-post#comment-123
     */
    public function testInsertCommentThrowsExceptionIfInsertFails()
    {
        $permalink = 'http://site.com/some-post#comment-123';

        $mockComment = "<item><guid>{$permalink}</guid><pubDate>2016-06-30 12:00:00</pubDate></item>";
        $mockComment = new SimpleXMLElement($mockComment);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->method('insertComment')
            ->willReturn(false);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedInsertCommentMethod = $reflectedBlogComment->getMethod('insertComment');
        $reflectedInsertCommentMethod->setAccessible(true);

        $reflectedInsertCommentMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            $mockComment,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertCommentReturnsTrueIfInsertSucceeds()
    {
        $mockComment = "<item><guid /><pubDate>2016-06-30 12:00:00</pubDate></item>";
        $mockComment = new SimpleXMLElement($mockComment);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->method('insertComment')
            ->willReturn(true);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);
        $reflectedInsertCommentMethod = $reflectedBlogComment->getMethod('insertComment');
        $reflectedInsertCommentMethod->setAccessible(true);

        $result = $reflectedInsertCommentMethod->invokeArgs($blogComment, [
            $mockBlogCommentModel,
            $mockComment,
            $mockDateTimeZone,
        ]);

        $this->assertTrue($result);
    }
}
