<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Blog as BlogModel;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use ReflectionClass;
use SimpleXMLElement;

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

    public function testConstructSetsExtendedPdo()
    {
        $mockContainer = $this->createMock(Container::class);
        $cron = new Blog($mockContainer);

        $this->assertAttributeSame($mockContainer, 'container', $cron);
    }

    public function testRunFetchesPosts()
    {
        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
            ]));

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkPostExists',
                'fetchPosts',
                'insertPost',
            ])
            ->getMock();
        $blog->expects($this->never())
            ->method('checkPostExists');
        $blog->expects($this->once())
            ->method('fetchPosts')
            ->with($mockClient)
            ->willReturn([]);
        $blog->expects($this->never())
            ->method('insertPost');

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $blog->run();
    }

    public function testRunChecksIfEachPostExists()
    {
        $posts = [
            new SimpleXMLElement('<guid>http://site.com/some-post</guid>'),
            new SimpleXMLElement('<guid>http://site.com/some-other-post</guid>'),
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockClient = $this->createMock(Client::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
                [ 'blogModel', $mockBlogModel ],
            ]));

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkPostExists',
                'fetchPosts',
                'insertPost',
            ])
            ->getMock();
        $blog->expects($this->exactly(count($posts)))
            ->method('checkPostExists')
            ->withConsecutive(
                [ $mockBlogModel, $posts[0]->guid ],
                [ $mockBlogModel, $posts[1]->guid ]
            )
            ->willReturn(true);
        $blog->method('fetchPosts')
            ->with($mockClient)
            ->willReturn($posts);
        $blog->expects($this->never())
            ->method('insertPost');

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $blog->run();
    }

    public function testRunPassesOntoInsertIfPostNotExists()
    {
        $posts = [
            new SimpleXMLElement('<guid>http://site.com/some-post</guid>'),
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockClient = $this->createMock(Client::class);
        $mockTimezone = $this->createMock(DateTimeZone::class);

        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')
            ->will($this->returnValueMap([
                [ 'blogClient', $mockClient ],
                [ 'blogModel', $mockBlogModel ],
                [ 'timezone', $mockTimezone ],
            ]));

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkPostExists',
                'fetchPosts',
                'insertPost',
            ])
            ->getMock();
        $blog->expects($this->exactly(count($posts)))
            ->method('checkPostExists')
            ->withConsecutive(
                [ $mockBlogModel, $posts[0]->guid ]
            )
            ->willReturn(false);
        $blog->method('fetchPosts')
            ->with($mockClient)
            ->willReturn($posts);
        $blog->expects($this->exactly(count($posts)))
            ->method('insertPost')
            ->withConsecutive(
                [ $mockBlogModel, $posts[0], $mockTimezone ]
            );

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedContainerProperty = $reflectedBlog->getProperty('container');
        $reflectedContainerProperty->setAccessible(true);
        $reflectedContainerProperty->setValue($blog, $mockContainer);

        $blog->run();
    }

    public function testFetchPostsPullsFromClient()
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
                $this->equalTo('rss.xml')
            )
            ->willReturn($mockResponse);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedFetchPostsMethod = $reflectedBlog->getMethod('fetchPosts');
        $reflectedFetchPostsMethod->setAccessible(true);

        $reflectedFetchPostsMethod->invokeArgs($blog, [
            $mockClient,
        ]);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Error while trying to fetch rss feed: 400
     */
    public function testFetchPostsThrowsExceptionOnNon200Status()
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
                $this->equalTo('rss.xml')
            )
            ->willReturn($mockResponse);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedFetchPostsMethod = $reflectedBlog->getMethod('fetchPosts');
        $reflectedFetchPostsMethod->setAccessible(true);

        $reflectedFetchPostsMethod->invokeArgs($blog, [
            $mockClient,
        ]);
    }

    public function testFetchPostsReturnsItems()
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
                $this->equalTo('rss.xml')
            )
            ->willReturn($mockResponse);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedFetchPostsMethod = $reflectedBlog->getMethod('fetchPosts');
        $reflectedFetchPostsMethod->setAccessible(true);

        $result = $reflectedFetchPostsMethod->invokeArgs($blog, [
            $mockClient,
        ]);

        $this->assertEquals($xmlItems, $result);
    }

    public function testCheckPostExistsPullsFromBlogModel()
    {
        $permalink = 'http://site.com/some-post';

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->expects($this->once())
            ->method('getPostByPermalink')
            ->with(
                $this->equalTo($permalink)
            );

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedCheckPostExistsMethod = $reflectedBlog->getMethod('checkPostExists');
        $reflectedCheckPostExistsMethod->setAccessible(true);

        $reflectedCheckPostExistsMethod->invokeArgs($blog, [
            $mockBlogModel,
            $permalink,
        ]);
    }

    public function testCheckPostExistsReturnsTrueIfRecordExists()
    {
        $post = [
            'id' => '123',
            'permalink' => 'http://site.com/some-post',
        ];

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->method('getPostByPermalink')
            ->willReturn($post);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedCheckPostExistsMethod = $reflectedBlog->getMethod('checkPostExists');
        $reflectedCheckPostExistsMethod->setAccessible(true);

        $result = $reflectedCheckPostExistsMethod->invokeArgs($blog, [
            $mockBlogModel,
            '',
        ]);

        $this->assertTrue($result);
    }

    public function testCheckPostExistsReturnsFalsesIfRecordNotExists()
    {
        $post = false;

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->method('getPostByPermalink')
            ->willReturn($post);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedCheckPostExistsMethod = $reflectedBlog->getMethod('checkPostExists');
        $reflectedCheckPostExistsMethod->setAccessible(true);

        $result = $reflectedCheckPostExistsMethod->invokeArgs($blog, [
            $mockBlogModel,
            '',
        ]);

        $this->assertFalse($result);
    }

    public function testInsertPostCastsDateToDateTime()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->expects($this->once())
            ->method('insertPost')
            ->with(
                $this->anything(),
                $this->equalTo($dateTime),
                $this->anything()
            );

        $mockPost = "<item><guid /><pubDate>{$date}</pubDate></item>";
        $mockPost = new SimpleXMLElement($mockPost);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedInsertPostMethod = $reflectedBlog->getMethod('insertPost');
        $reflectedInsertPostMethod->setAccessible(true);

        $reflectedInsertPostMethod->invokeArgs($blog, [
            $mockBlogModel,
            $mockPost,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertPostSetsDateTimeZone()
    {
        $date = '2016-06-30 12:00:00 +000';
        $timezone = 'America/Phoenix'; // always +700, no DST

        $dateTimeZone = new DateTimeZone($timezone);
        $dateTime = new DateTime($date);
        $dateTime->setTimezone($dateTimeZone);

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->expects($this->once())
            ->method('insertPost')
            ->with(
                $this->anything(),
                $this->callback(function ($param) use ($dateTime) {
                    return $param->getTimeZone()->getName() == $dateTime->getTimeZone()->getName();
                }),
                $this->anything()
            );

        $mockPost = "<item><guid /><pubDate>{$date}</pubDate></item>";
        $mockPost = new SimpleXMLElement($mockPost);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedInsertPostMethod = $reflectedBlog->getMethod('insertPost');
        $reflectedInsertPostMethod->setAccessible(true);

        $reflectedInsertPostMethod->invokeArgs($blog, [
            $mockBlogModel,
            $mockPost,
            $dateTimeZone,
        ]);
    }

    public function testInsertPostSendsParamsToBlogModel()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $permalink = 'http://site.com/some-post';

        $post = "<item><guid>{$permalink}</guid><pubDate>{$date}</pubDate></item>";
        $post = new SimpleXMLElement($post);

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->expects($this->once())
            ->method('insertPost')
            ->with(
                $this->equalTo($permalink),
                $this->equalTo($dateTime),
                $this->equalTo(json_encode($post))
            );

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedInsertPostMethod = $reflectedBlog->getMethod('insertPost');
        $reflectedInsertPostMethod->setAccessible(true);

        $reflectedInsertPostMethod->invokeArgs($blog, [
            $mockBlogModel,
            $post,
            $mockDateTimeZone,
        ]);
    }

    public function testInsertPostReturnsResultFromBlogModel()
    {
        $expectedResult = true;

        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->method('insertPost')
            ->willReturn($expectedResult);

        $mockPost = "<item><guid /><pubDate>{$date}</pubDate></item>";
        $mockPost = new SimpleXMLElement($mockPost);

        $mockDateTimeZone = $this->createMock(DateTimeZone::class);

        $blog = $this->getMockBuilder(Blog::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedInsertPostMethod = $reflectedBlog->getMethod('insertPost');
        $reflectedInsertPostMethod->setAccessible(true);

        $result = $reflectedInsertPostMethod->invokeArgs($blog, [
            $mockBlogModel,
            $mockPost,
            $mockDateTimeZone,
        ]);

        $this->assertSame($expectedResult, $result);
    }
}
