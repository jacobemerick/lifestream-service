<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\BlogComment as BlogCommentModel;
use SimpleXMLElement;

class BlogComment implements CronInterface
{

    /** @var Container */
    protected $container;

    /**
     * @param Container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function run()
    {
        $comments = $this->fetchComments($this->container->get('blogClient'));
        foreach ($comments as $comment) {
            $commentExists = $this->checkCommentExists(
                $this->container->get('blogCommentModel'),
                (string) $comment->guid
            );
            if ($commentExists) {
                continue;
            }

            $this->insertComment(
                $this->container->get('blogCommentModel'),
                $comment,
                $this->container->get('timezone')
            );
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

        return $blogCommentModel->insertComment((string) $comment->guid, $datetime, json_encode($comment));
    }
}
