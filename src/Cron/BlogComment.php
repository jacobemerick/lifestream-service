<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\BlogComment as BlogCommentModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class BlogComment implements CronInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    /** @var Container */
    protected $container;

    /**
     * @param Container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
 
        $this->logger = new NullLogger;
    }

    public function run()
    {
        try {
            $comments = $this->fetchComments($this->container->get('blogClient'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        foreach ($comments as $comment) {
            $commentExists = $this->checkCommentExists(
                $this->container->get('blogCommentModel'),
                (string) $comment->guid
            );
            if ($commentExists) {
                continue;
            }

            try {
                $this->insertComment(
                    $this->container->get('blogCommentModel'),
                    $comment,
                    $this->container->get('timezone')
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Inserted new blog comment: {$comment->guid}");
        }
    }

    /**
     * @param Client $client
     */
    protected function fetchComments(Client $client)
    {
        $response = $client->request('GET', 'rss-comments.xml');
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Error while trying to fetch rss feed: {$response->getStatusCode()}");
        }

        $rssString = (string) $response->getBody();
        $rss = new SimpleXMLElement($rssString);
        return $rss->channel->item;
    }

    /**
     * @param BlogCommentModel $blogCommentModel
     * @param string $permalink
     * @return boolean
     */
    protected function checkCommentExists(BlogCommentModel $blogCommentModel, $permalink)
    {
        $comment = $blogCommentModel->getCommentByPermalink($permalink);
        return $comment !== false;
    }

    /**
     * @param BlogCommentModel $blogCommentModel
     * @param SimpleXMLElement $comment
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertComment(
        BlogCommentModel $blogCommentModel,
        SimpleXMLElement $comment,
        DateTimeZone $timezone
    ) {
        $datetime = new DateTime($comment->pubDate);
        $datetime->setTimezone($timezone);

        $result = $blogCommentModel->insertComment((string) $comment->guid, $datetime, json_encode($comment));
        if (!$result) {
            throw new Exception("Error while trying to insert new comment: {$comment->guid}");
        }

        return true;
    }
}
