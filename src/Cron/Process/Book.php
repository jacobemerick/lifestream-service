<?php

namespace Jacobemerick\LifestreamService\Cron\Process;

use DateTime;
use Exception;
use stdclass;

use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Cron\CronInterface;
use Jacobemerick\LifestreamService\Model\Book as BookModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Book implements CronInterface, LoggerAwareInterface
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
            $books = $this->fetchBooks($this->container->get('bookModel'));
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        foreach ($books as $book) {
            $event = $this->getEvent(
                $this->container->get('eventModel'),
                'book',
                $book['id']
            );

            if ($event) {
                continue;
            }

            try {
                $bookMetadata = json_decode($book['metadata']);
                $description = $this->getDescription($bookMetadata);
                $descriptionHtml = $this->getDescriptionHtml($bookMetadata);

                $this->insertEvent(
                    $this->container->get('eventModel'),
                    $this->container->get('typeModel'),
                    $this->container->get('userModel'),
                    $description,
                    $descriptionHtml,
                    (new DateTime($book['datetime'])),
                    (object) [],
                    'Jacob Emerick',
                    'book',
                    $book['id']
                );
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $this->logger->debug("Added book event: {$book['id']}");
        }
    }

    /**
     * @param BookModel $bookModel
     * @return array
     */
    protected function fetchBooks(BookModel $bookModel)
    {
        return $bookModel->getBooks();
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescription(stdclass $metadata)
    {
        return sprintf(
            'Read %s by %s.',
            $metadata->title,
            $metadata->author_name
        );
    }

    /**
     * @param stdclass $metadata
     * @return string
     */
    protected function getDescriptionHtml(stdclass $metadata)
    {
        $description = '';
        if (isset($metadata->book_large_image_url)) {
            $description .= sprintf(
                '<img src="%s" alt="Goodreads | %s" />',
                str_replace('http:', 'https:', $metadata->book_large_image_url),
                $metadata->title
            );
        }

        $description .= sprintf(
            '<p>Read %s by %s.</p>',
            $metadata->title,
            $metadata->author_name
        );

        return $description;
    }
}
