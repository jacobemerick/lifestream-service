<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Blog as BlogModel;
use SimpleXMLElement;

class Blog implements CronInterface
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
        $posts = $this->fetchPosts($this->container->get('blogClient'));
        foreach ($posts as $post) {
            $postExists = $this->checkPostExists($this->container->get('blogModel'), (string) $post->guid);
            if ($postExists) {
                continue;
            }

            $this->insertPost($this->container->get('blogModel'), $post, $this->container->get('timezone'));
        }
    }

    /**
     * @param Client $client
     */
    protected function fetchPosts(Client $client)
    {
        $response = $client->get('rss.xml');
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
