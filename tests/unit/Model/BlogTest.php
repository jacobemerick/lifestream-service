<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class BlogTest extends TestCase
{

    public function testIsInstanceOfBlog()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Blog($mockPdo);

        $this->assertInstanceOf(Blog::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Blog($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetPostByPermalinkSendsParams()
    {
        $permalink = 'http://site.com/some-post';

        $query = "
            SELECT `id`, `permalink`, `datetime`, `metadata`
            FROM `blog`
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

        $model = new Blog($mockPdo);
        $model->getPostByPermalink($permalink);
    }

    public function testGetPostByPermalinkReturnsPost()
    {
        $post = [
            'id' => 1,
            'permalink' => 'http://site.com/some-post',
            'datetime' => '2016-06-30 12:00:00',
            'metadata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($post);

        $model = new Blog($mockPdo);
        $result = $model->getPostByPermalink('');

        $this->assertSame($post, $result);
    }

    public function testInsertPostSendsParams()
    {
        $permalink = 'http://site.com/some-post';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $query = "
            INSERT INTO `blog` (`permalink`, `datetime`, `metadata`)
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

        $model = new Blog($mockPdo);
        $model->insertPost($permalink, $datetime, $metadata);
    }

    public function testInsertPostReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Blog($mockPdo);
        $result = $model->insertPost('', new DateTime(), '');

        $this->assertTrue($result);
    }

    public function testInsertPostReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Blog($mockPdo);
        $result = $model->insertPost('', new DateTime(), '');

        $this->assertFalse($result);
    }
}
