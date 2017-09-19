<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use Exception;

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

            $this->logger->debug("Added new blog post: {$post['id']}");
        }
    }

    /**
     * @param BlogModel $model
     * @return array
     */
    protected function fetchPosts(BlogModel $model)
    {
    }

    /**
     * @param array $post
     * @return string
     */
    protected function getDescription(array $post)
    {
    }

    /**
     * @param array $post
     * @return string
     */
    protected function getDescriptionHtml(array $post)
    {
    }
}
