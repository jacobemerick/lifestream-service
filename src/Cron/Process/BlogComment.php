<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\BlogComment as BlogCommentModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class BlogComment implements CronInterface, LoggerAwareInterface
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

        $posts = array_map([ $this, 'collectComments' ], $posts);

        foreach ($posts as $post) {
            $event = $this->getEvent(
                $this->container->get('eventModel'),
                'blog',
                $post['id']
            );

            if ($event) {
                continue;
            }

            $previousMetadata = json_decode($event['metadata']);
            if (!$post['comments']) {
                $newMetadata = (object) [];
            } else {
                $newMetadata = (object) [
                    'comments' => $post['comments'],
                ];
            }

            if ($previousMetadata == $newMetadata) {
                continue;
            }

            try {
                $this->updateEvent(
                    $this->container->get('eventModel'),
                    $event['id'],
                    $newMetadata
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Update blog event: {$post['id']}");
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
     * @return array
     */
    protected function collectComments(array $post)
    {
        return $post;
    }
}
