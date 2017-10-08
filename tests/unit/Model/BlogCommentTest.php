<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class BlogCommentTest extends TestCase
{

    public function testIsInstanceOfBlogComment()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new BlogComment($mockPdo);

        $this->assertInstanceOf(BlogComment::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new BlogComment($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetCommentByPermalinkSendsParams()
    {
        $permalink = 'http://site.com/some-post#comment-123';

        $query = "
            SELECT `id`, `permalink`, `datetime`, `metadata`
            FROM `blog_comment`
            WHERE `permalink` = :permalink";
        $bindings = [
            'permalink' => $permalink,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new BlogComment($mockPdo);
        $model->getCommentByPermalink($permalink);
    }

    public function testGetCommentByPermalinkReturnsComment()
    {
        $post = [
            'id' => 1,
            'permalink' => 'http://site.com/some-post#comment-123',
            'datetime' => '2016-06-30 12:00:00',
            'metadata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($post);

        $model = new BlogComment($mockPdo);
        $result = $model->getCommentByPermalink('');

        $this->assertSame($post, $result);
    }

    public function testGetCommentCountByPageSendsParams()
    {
        $permalink = 'http://site.com/some-post';

        $query = "
            SELECT COUNT(1)
            FROM `blog_comment`
            WHERE `permalink` LIKE :permalink";
        $bindings = [
            'permalink' => "{$permalink}%",
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchValue')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new BlogComment($mockPdo);
        $model->getCommentCountByPage($permalink);
    }

    public function testGetCommentCountByPageReturnsCount()
    {
        $count = 3;

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchValue')
            ->willReturn($count);

        $model = new BlogComment($mockPdo);
        $result = $model->getCommentCountByPage('');

        $this->assertSame($count, $result);
    }

    public function testInsertCommentSendsParams()
    {
        $permalink = 'http://site.com/some-post#comment-123';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $query = "
            INSERT INTO `blog_comment` (`permalink`, `datetime`, `metadata`)
            VALUES (:permalink, :datetime, :metadata)";
        $bindings = [
            'permalink' => $permalink,
            'datetime' => $datetime->format('Y-m-d H:i:s'),
            'metadata' => $metadata,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAffected')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new BlogComment($mockPdo);
        $model->insertComment($permalink, $datetime, $metadata);
    }

    public function testInsertCommentReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new BlogComment($mockPdo);
        $result = $model->insertComment('', new DateTime(), '');

        $this->assertTrue($result);
    }

    public function testInsertCommentReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new BlogComment($mockPdo);
        $result = $model->insertComment('', new DateTime(), '');

        $this->assertFalse($result);
    }
}
