<?php

namespace Jacobemerick\LifestreamService\Model;

use Aura\Sql\ExtendedPdo;
use DateTime;
use PHPUnit\Framework\TestCase;

class TwitterTest extends TestCase
{

    public function testIsInstanceOfTwitter()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Twitter($mockPdo);

        $this->assertInstanceOf(Twitter::class, $model);
    }

    public function testConstructSetsExtendedPdo()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $model = new Twitter($mockPdo);

        $this->assertAttributeSame($mockPdo, 'extendedPdo', $model);
    }

    public function testGetTweetByTweetIdSendsParams()
    {
        $tweetId = '123';

        $query = "
            SELECT `id`, `datetime`, `metadata`
            FROM `twitter`
            WHERE `tweet_id` = :tweet_id";
        $bindings = [
            'tweet_id' => $tweetId,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Twitter($mockPdo);
        $model->getTweetByTweetId($tweetId);
    }

    public function testGetTweetByTweetIdReturnsTweet()
    {
        $tweet = [
            'id' => 1,
            'datetime' => '2016-06-30 12:00:00',
            'metadata' => '{"key":"value"}',
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchOne')
            ->willReturn($tweet);

        $model = new Twitter($mockPdo);
        $result = $model->getTweetByTweetId('');

        $this->assertSame($tweet, $result);
    }

    public function testGetTweetsReturnsTweets()
    {
        $tweets = [
            [ 'id' => 1 ],
            [ 'id' => 2 ],
        ];

        $query = "
            SELECT `id`, `tweet_id`, `datetime`, `metadata`
            FROM `twitter`";

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo($query)
            )
            ->willReturn($tweets);

        $model = new Twitter($mockPdo);
        $result = $model->getTweets();

        $this->assertSame($tweets, $result);
    }

    public function testInsertTweetSendsParams()
    {
        $tweetId = '123';
        $datetime = new DateTime();
        $metadata = '{"key":"value"}';

        $query = "
            INSERT INTO `twitter` (`tweet_id`, `datetime`, `metadata`)
            VALUES (:tweet_id, :datetime, :metadata)";
        $bindings = [
            'tweet_id' => $tweetId,
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

        $model = new Twitter($mockPdo);
        $model->insertTweet($tweetId, $datetime, $metadata);
    }

    public function testInsertTweetReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Twitter($mockPdo);
        $result = $model->insertTweet('', new DateTime(), '');

        $this->assertTrue($result);
    }

    public function testInsertTweetReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Twitter($mockPdo);
        $result = $model->insertTweet('', new DateTime(), '');

        $this->assertFalse($result);
    }

    public function testUpdateTweetSendsParams()
    {
        $tweetId = '123';
        $metadata = '{"key":"value"}';

        $query = "
            UPDATE `twitter`
            SET `metadata` = :metadata
            WHERE `tweet_id` = :tweet_id
            LIMIT 1";
        $bindings = [
            'tweet_id' => $tweetId,
            'metadata' => $metadata,
        ];

        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->expects($this->once())
            ->method('fetchAffected')
            ->with(
                $this->equalTo($query),
                $this->equalTo($bindings)
            );

        $model = new Twitter($mockPdo);
        $model->updateTweet($tweetId, $metadata);
    }

    public function testUpdateTweetReturnsTrueIfSuccess()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(1);

        $model = new Twitter($mockPdo);
        $result = $model->updateTweet('', '');

        $this->assertTrue($result);
    }

    public function testUpdateTweetReturnsFalseIfFailure()
    {
        $mockPdo = $this->createMock(ExtendedPdo::class);
        $mockPdo->method('fetchAffected')
            ->willReturn(0);

        $model = new Twitter($mockPdo);
        $result = $model->updateTweet('', '');

        $this->assertFalse($result);
    }
}
