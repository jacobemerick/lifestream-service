<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Blog as BlogModel;
use PHPUnit\Framework\TestCase;
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
        $this->markTestIncomplete();
    }

    public function testRunChecksIfEachPostExists()
    {
        $this->markTestIncomplete();
    }

    public function testRunSkipsInsertIfPostExists()
    {
        $this->markTestIncomplete();
    }

    public function testRunPassesOntoInsertIfPostNotExists()
    {
        $this->markTestIncomplete();
    }

    public function testFetchPostsPullsFromClient()
    {
        $this->markTestIncomplete();
    }

    public function testFetchPostsThrowsExceptionOnNon200Status()
    {
        $this->markTestIncomplete();
    }

    public function testFetchPostsCastsResponseToXML()
    {
        $this->markTestIncomplete();
    }

    public function testFetchPostsReturnsItems()
    {
        $this->markTestIncomplete();
    }

    public function testCheckPostExistsPullsFromBlogModel()
    {
        $this->markTestIncomplete();
    }

    public function testCheckPostExistsReturnsTrueIfRecordExists()
    {
        $this->markTestIncomplete();
    }

    public function testCheckPostExistsReturnsFalsesIfRecordNotExists()
    {
        $this->markTestIncomplete();
    }

    public function testInsertPostCastsDateToDateTime()
    {
        $date = '2016-06-30 12:00:00';
        $dateTime = new DateTime($date);

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedInsertPostMethod = $reflectedBlog->getMethod('insertPost');
        $reflectedInsertPostMethod->setAccessible(true);

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->method('insertPost')
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

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedInsertPostMethod = $reflectedBlog->getMethod('insertPost');
        $reflectedInsertPostMethod->setAccessible(true);

        $mockBlogModel = $this->createMock(BlogModel::class);
        $mockBlogModel->method('insertPost')
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

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedInsertPostMethod = $reflectedBlog->getMethod('insertPost');
        $reflectedInsertPostMethod->setAccessible(true);

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

        $reflectedBlog = new ReflectionClass(Blog::class);
        $reflectedInsertPostMethod = $reflectedBlog->getMethod('insertPost');
        $reflectedInsertPostMethod->setAccessible(true);

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

        $result = $reflectedInsertPostMethod->invokeArgs($blog, [
            $mockBlogModel,
            $mockPost,
            $mockDateTimeZone,
        ]);

        $this->assertSame($expectedResult, $result);
    }
}
