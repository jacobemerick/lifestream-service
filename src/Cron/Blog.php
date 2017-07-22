<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;


use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Blog as BlogModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Blog implements CronInterface, LoggerAwareInterface
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
            $posts = $this->fetchPosts($this->container->get('blogClient'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        foreach ($posts as $post) {
            $postExists = $this->checkPostExists($this->container->get('blogModel'), (string) $post->guid);
            if ($postExists) {
                continue;
            }

            try {
                $this->insertPost(
                    $this->container->get('blogModel'),
                    $post,
                    $this->container->get('timezone')
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Inserted new blog post: {$post->guid}");
        }
    }

    /**
     * @param Client $client
     * @return array
     */
    protected function fetchPosts(Client $client)
    {
        $response = $client->request('GET', 'rss.xml');
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Error while trying to fetch rss feed: {$response->getStatusCode()}");
        }

        $rssString = (string) $response->getBody();
        $rss = new SimpleXMLElement($rssString);
        return $rss->channel->item;
    }

    /**
     * @param BlogModel $blogModel
     * @param string $permalink
     * @return boolean
     */
    protected function checkPostExists(BlogModel $blogModel, $permalink)
    {
        $post = $blogModel->getPostByPermalink($permalink);
        return $post !== false;
    }

    /**
     * @param BlogModel $blogModel
     * @param SimpleXMLElement $post
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertPost(BlogModel $blogModel, SimpleXMLElement $post, DateTimeZone $timezone)
    {
        $datetime = new DateTime($post->pubDate);
        $datetime->setTimezone($timezone);

        return $blogModel->insertPost((string) $post->guid, $datetime, json_encode($post));
    }
}
