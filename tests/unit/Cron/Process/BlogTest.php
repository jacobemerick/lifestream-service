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
use Jacobemerick\LifestreamService\Model\Event as EventModel;
use Jacobemerick\LifestreamService\Model\Type as TypeModel;
use Jacobemerick\LifestreamService\Model\User as UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface as Logger;
use Psr\Log\NullLogger;

class BlogTest extends TestCase
{

    public function testIsInstanceOfBlog()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Blog($mockContainer);

        $this->assertInstanceOf(Blog::class, $cron);
    }

    public function testIsInstanceOfCronInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Blog($mockContainer);

        $this->assertInstanceOf(CronInterface::class, $cron);
    }

    public function testIsInstanceOfLoggerAwareInterface()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Blog($mockContainer);

        $this->assertInstanceOf(LoggerAwareInterface::class, $cron);
    }

    public function testConstructSetsContainer()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Blog($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testConstructSetsNullLogger()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Blog($mockContainer);

        $this->assertAttributeInstanceOf(NullLogger::class, 'logger', $cron);
    }

    public function testRunFetchesPosts()
    {
        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchPosts',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $blog->expects($this->once())
            ->method('fetchPosts')
            ->with($mockBlogModel)
            ->willReturn([]);
        $blog->expects($this->never())
            ->method('getDescription');
        $blog->expects($this->never())
            ->method('getDescriptionHtml');
        $blog->expects($this->never())
            ->method('getEvent');
        $blog->expects($this->never())
            ->method('insertEvent');

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlog->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blog, $mockLogger);

        $blog->run();
    }

    public function testRunLogsThrownExceptionsFromFetchPosts()
    {
        $mockExceptionMessage = 'Failed to fetch posts';
        $mockException = new Exception($mockExceptionMessage);

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchPosts',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $blog->method('fetchPosts')
            ->will($this->throwException($mockException));
        $blog->expects($this->never())
            ->method('getDescription');
        $blog->expects($this->never())
            ->method('getDescriptionHtml');
        $blog->expects($this->never())
            ->method('getEvent');
        $blog->expects($this->never())
            ->method('insertEvent');

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlog->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blog, $mockLogger);

        $blog->run();
    }

    public function testRunGetEventForEachPost()
    {
        $posts = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->never())
            ->method('error');

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchPosts',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $blog->method('fetchPosts')
            ->willReturn($posts);
        $blog->expects($this->never())
            ->method('getDescription');
        $blog->expects($this->never())
            ->method('getDescriptionHtml');
        $blog->expects($this->exactly(count($posts)))
            ->method('getEvent')
            ->withConsecutive(
                [ $mockEventModel, 'blog', $posts[0]['id'] ],
                [ $mockEventModel, 'blog', $posts[1]['id'] ]
            )
            ->willReturn(true);
        $blog->expects($this->never())
            ->method('insertEvent');

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlog->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blog, $mockLogger);

        $blog->run();
    }

    public function testRunFormatsDescription()
    {
        $postMetadata = (object) [
            'title' => 'some title',
        ];

        $posts = [
            [
                'id' => 1,
                'metadata' => json_encode($postMetadata),
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchPosts',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $blog->method('fetchPosts')
            ->willReturn($posts);
        $blog->expects($this->once())
            ->method('getDescription')
            ->with(
                $this->equalTo($postMetadata)
            );
        $blog->method('getEvent')
            ->willReturn(false);

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlog->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blog, $mockLogger);

        $blog->run();
    }

    public function testRunFormatsDescriptionHtml()
    {
        $postMetadata = (object) [
            'title' => 'some title',
        ];

        $posts = [
            [
                'id' => 1,
                'metadata' => json_encode($postMetadata),
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchPosts',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $blog->method('fetchPosts')
            ->willReturn($posts);
        $blog->expects($this->once())
            ->method('getDescriptionHtml')
            ->with(
                $this->equalTo($postMetadata)
            );
        $blog->method('getEvent')
            ->willReturn(false);

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlog->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blog, $mockLogger);

        $blog->run();
    }

    public function testRunPassesParamsToInsertEvent()
    {
        $description = 'some description';
        $descriptionHtml = '<p>some description</p>';
        $datetime = '2016-06-30 12:00:00';
        $postId = 1;

        $posts = [
            [
                'id' => $postId,
                'metadata' => '{}',
                'datetime' => $datetime,
            ],
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchPosts',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $blog->method('fetchPosts')
            ->willReturn($posts);
        $blog->method('getDescription')
            ->willReturn($description);
        $blog->method('getDescriptionHtml')
            ->willReturn($descriptionHtml);
        $blog->method('getEvent')
            ->willReturn(false);
        $blog->expects($this->once())
            ->method('insertEvent')
            ->with(
                $this->equalTo($mockEventModel),
                $this->equalTo($mockTypeModel),
                $this->equalTo($mockUserModel),
                $this->equalTo($description),
                $this->equalTo($descriptionHtml),
                $this->callback(function ($datetimeParam) use ($datetime) {
                    return $datetimeParam->format('Y-m-d H:i:s') === $datetime;
                }),
                $this->callback(function ($metadata) {
                    return $metadata == new stdclass;
                }),
                $this->equalTo('Jacob Emerick'),
                $this->equalTo('blog'),
                $this->equalTo($postId)
            );

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlog->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blog, $mockLogger);

        $blog->run();
    }

    public function testRunLogsThrownExceptionFromInsertEvent()
    {
        $mockExceptionMessage = 'Failed to insert post';
        $mockException = new Exception($mockExceptionMessage);

        $posts = [
            [
                'id' => 1,
                'metadata' => '{}',
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];
 
        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->never())
            ->method('debug');
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->equalTo($mockExceptionMessage));

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchPosts',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $blog->method('fetchPosts')
            ->willReturn($posts);
        $blog->method('getEvent')
            ->willReturn(false);
        $blog->method('insertEvent')
            ->will($this->throwException($mockException));

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlog->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blog, $mockLogger);

        $blog->run();
    }

    public function testRunLogsInsertedEventIfSuccessful()
    {
        $posts = [
            [
                'id' => 1,
                'metadata' => '{}',
                'datetime' => '2016-06-30 12:00:00',
            ],
        ];
 
        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockEventModel = $this->createMock(EventModel::class);
        $mockTypeModel = $this->createMock(TypeModel::class);
        $mockUserModel = $this->createMock(UserModel::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogModel', $mockBlogModel ],
                [ 'eventModel', $mockEventModel ],
                [ 'typeModel', $mockTypeModel ],
                [ 'userModel', $mockUserModel ],
            ]));

        $mockLogger = $this->createMock(Logger::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->equalTo('Added blog event: 1'));
        $mockLogger->expects($this->never())
            ->method('error');

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchPosts',
                'getDescription',
                'getDescriptionHtml',
                'getEvent',
                'insertEvent',
            ])
            ->getMock();
        $blog->method('fetchPosts')
            ->willReturn($posts);
        $blog->method('getEvent')
            ->willReturn(false);

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $reflectedLoggerProperty = $reflectedBlog->getProperty('logger');
        $reflectedLoggerProperty->setAccessible(true);
        $reflectedLoggerProperty->setValue($blog, $mockLogger);

        $blog->run();
    }

    public function testFetchPostsPullsFromModel()
    {
        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->expects($this->once())
            ->method('getPosts');

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedFetchPostsMethod = $reflectedBlog->getMethod('fetchPosts');
        $reflectedFetchPostsMethod->setAccessible(true);

        $reflectedFetchPostsMethod->invokeArgs($blog, [
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

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedFetchPostsMethod = $reflectedBlog->getMethod('fetchPosts');
        $reflectedFetchPostsMethod->setAccessible(true);

        $result = $reflectedFetchPostsMethod->invokeArgs($blog, [
            $mockBlogModel,
        ]);

        $this->assertEquals($posts, $result);
    }

    public function testGetDescriptionFormatsDescription()
    {
        $metadata = (object) [
            'category' => 'some-category',
            'title' => 'some title',
        ];

        $expectedDescription = 'Blogged about some category | some title.';

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedFetchPostsMethod = $reflectedBlog->getMethod('getDescription');
        $reflectedFetchPostsMethod->setAccessible(true);

        $result = $reflectedFetchPostsMethod->invokeArgs($blog, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetDescriptionHtmlFormatsDescriptionWithEnclosure()
    {
        $metadata = (object) [
            'enclosure' => (object) [
                '@attributes' => (object) [
                    'url' => 'some-image.jpg',
                ],
            ],
            'title' => 'some title',
            'link' => 'some-link.html',
            'description' => 'some<p>description',
        ];

        $expectedDescription = '';
        $expectedDescription .= '<img src="some-image.jpg" alt="Blog | some title" />';
        $expectedDescription .= '<h4><a href="some-link.html" title="Jacob Emerick\'s Blog | some title">some title</a></h4>';
        $expectedDescription .= '<p>some&lt;p&gt;description [<a href="some-link.html">read more</a></a>]</p>';

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedFetchPostsMethod = $reflectedBlog->getMethod('getDescriptionHtml');
        $reflectedFetchPostsMethod->setAccessible(true);

        $result = $reflectedFetchPostsMethod->invokeArgs($blog, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }

    public function testGetDescriptionHtmlFormatsDescriptionWithoutEnclosure()
    {
        $metadata = (object) [
            'title' => 'some title',
            'link' => 'some-link.html',
            'description' => 'some<p>description',
        ];

        $expectedDescription = '';
        $expectedDescription .= '<h4><a href="some-link.html" title="Jacob Emerick\'s Blog | some title">some title</a></h4>';
        $expectedDescription .= '<p>some&lt;p&gt;description [<a href="some-link.html">read more</a></a>]</p>';

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);

        $reflectedFetchPostsMethod = $reflectedBlog->getMethod('getDescriptionHtml');
        $reflectedFetchPostsMethod->setAccessible(true);

        $result = $reflectedFetchPostsMethod->invokeArgs($blog, [
            $metadata,
        ]);

        $this->assertEquals($expectedDescription, $result);
    }
}
