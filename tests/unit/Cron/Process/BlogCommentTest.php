<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use ReflectionClass;
use stdclass;

use PHPUnit\Framework\TestCase;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Blog as BlogModel;
use Jacobemerick\LifestreamService\Model\BlogComment as BlogCommentModel;
use Jacobemerick\LifestreamService\Model\Event as EventModel;
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

    public function testRunFetchesPosts()
    {
        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->expects($this->never())
            ->method('collectComments');
        $blogComment->expects($this->once())
            ->method('fetchPosts')
            ->with($mockBlogModel)
            ->willReturn([]);
        $blogComment->expects($this->never())
            ->method('getEvent');
        $blogComment->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunLogsThrownExceptionsFromFetchPosts()
    {
        $mockExceptionMessage = 'Failed to fetch posts';
        $mockException = new Exception($mockExceptionMessage);

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
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
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->expects($this->never())
            ->method('collectComments');
        $blogComment->method('fetchPosts')
            ->will($this->throwException($mockException));
        $blogComment->expects($this->never())
            ->method('getEvent');
        $blogComment->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunCollectsCommentsForPosts()
    {
        $posts = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->expects($this->exactly(count($posts)))
            ->method('collectComments')
            ->withConsecutive(
                [ $posts[0] ],
                [ $posts[1] ]
            )
            ->will($this->returnArgument(0));
        $blogComment->method('fetchPosts')
            ->willReturn($posts);
        $blogComment->method('getEvent')
            ->willReturn(false);
        $blogComment->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunGetEventForEachPost()
    {
        $posts = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->method('collectComments')
            ->will($this->returnArgument(0));
        $blogComment->method('fetchPosts')
            ->willReturn($posts);
        $blogComment->expects($this->exactly(count($posts)))
            ->method('getEvent')
            ->withConsecutive(
                [ $mockEventModel, 'blog', $posts[0]['id'] ],
                [ $mockEventModel, 'blog', $posts[1]['id'] ]
            )
            ->willReturn(false);
        $blogComment->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunIgnoresEmptyCommentCount()
    {
        $post = [
            'id' => 1,
        ];
        $postWithComments = [
            'id' => 1,
            'comments' => 0,
        ];

        $event = [
            'id' => 1,
            'metadata' => '{}',
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->method('collectComments')
            ->willReturn($postWithComments);
        $blogComment->method('fetchPosts')
            ->willReturn([ $post ]);
        $blogComment->method('getEvent')
            ->willReturn($event);
        $blogComment->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunIgnoresSameCommentCount()
    {
        $post = [
            'id' => 1,
        ];
        $postWithComments = [
            'id' => 1,
            'comments' => 2,
        ];

        $event = [
            'id' => 1,
            'metadata' => '{"comments":"2"}',
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->method('collectComments')
            ->willReturn($postWithComments);
        $blogComment->method('fetchPosts')
            ->willReturn([ $post ]);
        $blogComment->method('getEvent')
            ->willReturn($event);
        $blogComment->expects($this->never())
            ->method('updateEventMetadata');

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testRunUpdatesOnDifferentCommentCount()
    {
        $expectedMetadata = (object) [
            'comments' => '3',
        ];

        $post = [
            'id' => 1,
        ];
        $postWithComments = [
            'id' => 1,
            'comments' => 3,
        ];

        $event = [
            'id' => 1,
            'metadata' => '{"comments":"2"}',
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->method('collectComments')
            ->willReturn($postWithComments);
        $blogComment->method('fetchPosts')
            ->willReturn([ $post ]);
        $blogComment->method('getEvent')
            ->willReturn($event);
        $blogComment->expects($this->once())
            ->method('updateEventMetadata')
            ->with(
                $this->equalTo($mockEventModel),
                $this->equalTo($post['id']),
                $this->equalTo($expectedMetadata)
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

    public function testRunLogsThrownExceptionFromUpdateMetadata()
    {
        $mockExceptionMessage = 'Failed to update metadata';
        $mockException = new Exception($mockExceptionMessage);

        $post = [
            'id' => 1,
        ];
        $postWithComments = [
            'id' => 1,
            'comments' => 3,
        ];

        $event = [
            'id' => 1,
            'metadata' => '{"comments":"2"}',
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
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
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->method('collectComments')
            ->willReturn($postWithComments);
        $blogComment->method('fetchPosts')
            ->willReturn([ $post ]);
        $blogComment->method('getEvent')
            ->willReturn($event);
        $blogComment->method('updateEventMetadata')
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

    public function testRunLogsUpdatedMetadataIfSuccessful()
    {
        $post = [
            'id' => 1,
        ];
        $postWithComments = [
            'id' => 1,
            'comments' => 3,
        ];

        $event = [
            'id' => 1,
            'metadata' => '{"comments":"2"}',
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Update blog event: 1'));
        $mockLogger->expects($this->never())
            ->method('error');

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'collectComments',
                'fetchPosts',
                'getEvent',
                'updateEventMetadata',
            ])
            ->getMock();
        $blogComment->method('collectComments')
            ->willReturn($postWithComments);
        $blogComment->method('fetchPosts')
            ->willReturn([ $post ]);
        $blogComment->method('getEvent')
            ->willReturn($event);

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlogComment->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blogComment, $mockLogger);

        $blogComment->run();
    }

    public function testFetchPostsPullsFromModel()
    {
        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->expects($this->once())
            ->method('getPosts');

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedFetchPostsMethod = $reflectedBlogComment->getMethod('fetchPosts');
        $reflectedFetchPostsMethod->setAccessible(true);

        $reflectedFetchPostsMethod->invokeArgs($blogComment, [
            $mockBlogModel,
        ]);
    }

    public function testFetchPostsReturnsItems()
    {
        $posts = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->method('getPosts')
            ->willReturn($posts);

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedFetchPostsMethod = $reflectedBlogComment->getMethod('fetchPosts');
        $reflectedFetchPostsMethod->setAccessible(true);

        $result = $reflectedFetchPostsMethod->invokeArgs($blogComment, [
            $mockBlogModel,
        ]);

        $this->assertEquals($posts, $result);
    }

    public function testCollectCommentsPullsCountFromModel()
    {
        $post = [
            'permalink' => 'some permalink',
        ];

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->expects($this->once())
            ->method('getCommentCountByPage')
            ->with($post['permalink']);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogCommentModel', $mockBlogCommentModel ],
            ]));

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedCollectCommentsMethod = $reflectedBlogComment->getMethod('collectComments');
        $reflectedCollectCommentsMethod->setAccessible(true);

        $reflectedCollectCommentsMethod->invokeArgs($blogComment, [
            $post,
        ]);
    }

    public function testCollectCommentsReturnsCountFromModel()
    {
        $count = 2;

        $post = [
            'permalink' => 'some permalink',
        ];

        $expectedPost = [
            'permalink' => 'some permalink',
            'comments' => $count,
        ];

        $mockBlogCommentModel = $this->createMock(BlogCommentModel::class);
        $mockBlogCommentModel->method('getCommentCountByPage')
            ->willReturn($count);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogCommentModel', $mockBlogCommentModel ],
            ]));

        $blogComment = $this->getMockBuilder(BlogComment::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlogComment = new ReflectionClass(BlogComment::class);

        $reflectedContainerProperty = $reflectedBlogComment->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blogComment, $mockContainer);

        $reflectedCollectCommentsMethod = $reflectedBlogComment->getMethod('collectComments');
        $reflectedCollectCommentsMethod->setAccessible(true);

        $result = $reflectedCollectCommentsMethod->invokeArgs($blogComment, [
            $post,
        ]);

        $this->assertEquals($expectedPost, $result);
    }
}
