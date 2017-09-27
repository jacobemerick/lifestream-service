<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

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

            if ($event) {
                continue;
            }

            try {
                $postMetadata = json_decode($post['metadata']);
                $description = $this->getDescription($postMetadata);
                $descriptionHtml = $this->getDescriptionHtml($postMetadata);

                $this->insertEvent(
                    $this->container->get('eventModel'),
                    $this->container->get('typeModel'),
                    $this->container->get('userModel'),
                    $description,
                    $descriptionHtml,
                    (new DateTime($post['datetime'])),
                    [],
                    'Jacob Emerick',
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
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescription(stdclass $metadata)
    {
        return sprintf(
            'Blogged about %s | %s.',
            str_replace('-', ' ', $metadata->category),
            $metadata->title
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        if (isset($metadata->enclosure)) {
            $description .= sprintf(
                '<img src="%s" alt="Blog | %s" />',
                $metadata->enclosure->{'@attributes'}->url,
                $metadata->title
            );
        }

        $description .= sprintf(
            '<h4><a href="%s" title="Jacob Emerick\'s Blog | %s">%s</a></h4>',
            $metadata->link,
            $metadata->title,
            $metadata->title
        );

        $description .= sprintf(
            '<p>%s [<a href="%s">read more</a></a>]</p>',
            htmlentities($metadata->description),
            $metadata->link
        );

        return $description;
    }
}
