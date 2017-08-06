<?php

namespace Jacobemerick\LifestreamService\Cron;

use DateTime;
use DateTimeZone;
use Exception;
use SimpleXMLElement;

use GuzzleHttp\ClientInterface as Client;
use Interop\Container\ContainerInterface as Container;
use Jacobemerick\LifestreamService\Model\Book as BookModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Book implements CronInterface, LoggerAwareInterface
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
        $page = 1;
        $makeNewRequest = true;

        while ($makeNewRequest) {
            try {
                $shelf = $this->container->get('config')->book->shelf;
                $books = $this->fetchBooks($this->container->get('bookClient'), $shelf, $page);
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            $makeNewRequest = false;
            $this->logger->debug("Processing page {$page} of api results");

            foreach ($books as $book) {
                $isBookRead = !empty($book->user_read_at);
                if (!$isBookRead) {
                    continue;
                }

                $bookExists = $this->checkBookExists(
                    $this->container->get('bookModel'),
                    (string) $book->book_id
                );
                if ($bookExists) {
                    $makeNewRequest = false;
                    continue;
                }

                $makeNewRequest = true;
                try {
                    $this->insertBook(
                        $this->container->get('bookModel'),
                        $book,
                        $this->container->get('timezone')
                    );
                } catch (Exception $exception) {
                    $this->logger->error($exception->getMessage());
                    return;
                }

                $this->logger->debug("Inserted new book: {$book->book_id}");
            }

            $page++;
        }
    }

    /**
     * @param Client $client
     * @param string $shelf
     * @param integer $page
     * @return array
     */
    protected function fetchBooks(Client $client, $shelf, $page)
    {
        $response = $client->request(
            'GET',
            "/review/list_rss/{$shelf}",
            [
                'query' => [ 'page' => $page ],
            ]
        );
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Error while trying to fetch books: {$response->getStatusCode()}");
        }

        $rssString = (string) $response->getBody();
        $rss = new SimpleXMLElement($rssString, LIBXML_NOCDATA);
        return $rss->channel->item;
    }

    /**
     * @param BookModel $bookModel
     * @param string $bookId
     * @return boolean
     */
    protected function checkBookExists(BookModel $bookModel, $bookId)
    {
        $book = $bookModel->getBookByBookId($bookId);
        return $book !== false;
    }

    /**
     * @param BookModel $bookModel
     * @param SimpleXMLElement $book
     * @param DateTimeZone $timezone
     * @return boolean
     */
    protected function insertBook(BookModel $bookModel, SimpleXMLElement $book, DateTimeZone $timezone)
    {
        $datetime = new DateTime($book->user_read_at);
        $datetime->setTimezone($timezone);

        $result = $bookModel->insertBook(
            (string) $book->book_id,
            (string) $book->guid,
            $datetime,
            json_encode($book)
        );

        if (!$result) {
            throw new Exception("Error while trying to insert new book: {$book->book_id}");
        }

        return true;
    }
}
