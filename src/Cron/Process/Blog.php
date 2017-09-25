<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use Exception;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Blog as BlogModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Blog implements CronInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;
    use ProcessTrait;

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
            $posts = $this->fetchPosts($this->container->get('blogModel'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        foreach ($posts as $post) {
            $event = $this->getEvent(
                $this->container->get('eventModel'),
                'blog',
                $post['id']
            );
            if ($event !== null) {
                continue;
            }

            try {
                $description = $this->getDescription($post);
                $descriptionHtml = $this->getDescriptionHtml($post);

                $this->insertEvent(
                    $this->container->get('eventModel'),
                    $description,
                    $descriptionHtml,
                    (new DateTime($post['datetime'])),
                    [],
                    'blog',
                    $post['id']
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Added blog event: {$post['id']}");
        }
    }

    /**
     * @param BlogModel $blogModel
     * @return array
     */
    protected function fetchPosts(BlogModel $blogModel)
    {
        return $blogModel->getPosts();
    }

    /**
     * @param array $post
     * @return string
     */
    protected function getDescription(array $post)
    {
        return sprintf(
            'Blogged about %s | %s.',
            str_replace('-', ' ', $post['category']),
            $post['title']
        );
    }

    /**
     * @param array $post
     * @return string
     */
    protected function getDescriptionHtml(array $post)
    {
        $description = '';
        if ($post['enclosure']) {
            $description .= sprintf(
                '<img src="%s" alt="Blog | %s" />',
                $post['enclosure']['@attributes']['url'],
                $post['title']
            );
        }

        $description .= sprintf(
            '<h4><a href="%s" title="Jacob Emerick\'s Blog | %s">%s</a></h4>',
            $post['link'],
            $post['title'],
            $post['title']
        );

        $description .= sprintf(
            '<p>%s [<a href="%s">read more</a></a>]</p>',
            htmlentities($blogData['description']),
            $blogData['link']
        );

        return $description;
    }
}
