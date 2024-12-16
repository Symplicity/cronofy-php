<?php

namespace Cronofy;

use Cronofy\Exception\CronofyException;
use Cronofy\Http\Response;
use Cronofy\Interfaces\ConnectionInterface;
use Cronofy\Interfaces\ResponseIteratorInterface;
use Traversable;

class ResponseIterator implements ResponseIteratorInterface
{
    private $connection;
    private $itemsKey;
    private $urlParams;
    private $firstPage;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws CronofyException
     */
    public function setItems(string $url, string $itemKey, array $urlParams = []) : self
    {
        $this->itemsKey = $itemKey;
        $this->urlParams = $urlParams;
        $this->firstPage = $this->getPage($url);
        return $this;
    }

    public function getIterator(): \Generator
    {
        return $this->each();
    }

    public function each(): \Generator
    {
        $page = $this->firstPage;

        for ($i = 0; $i < count($page[$this->itemsKey]); $i++) {
            yield $page[$this->itemsKey][$i];
        }

        while (isset($page["pages"]["next_page"])) {
            $page = $this->getPage($page["pages"]["next_page"]);

            for ($i = 0; $i < count($page[$this->itemsKey]); $i++) {
                yield $page[$this->itemsKey][$i];
            }
        }
    }

    /**
     * @throws CronofyException
     */
    private function getPage(string $url) : array
    {
        try {
            $response = $this->connection->get($url, $this->urlParams);
            return Response::toArray($response);
        } catch (\Exception $e) {
            $msg = null;

            if (\method_exists($e, 'getResponse')) {
                $msg = Response::toArray($e->getResponse());
            }

            throw new CronofyException($e->getMessage(), $e->getCode(), $msg);
        }
    }
}
