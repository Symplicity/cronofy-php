<?php

namespace Cronofy;

use Cronofy\Exception\CronofyException;
use Cronofy\Http\Response;
use Cronofy\Interfaces\ConnectionInterface;
use Cronofy\Interfaces\ResponseIteratorInterface;

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

    public function setItems(string $url, string $itemKey, array $urlParams = []) : self
    {
        $this->itemsKey = $itemKey;
        $this->urlParams = $urlParams;
        $this->firstPage = $this->getPage($url);
        return $this;
    }

    public function each()
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

    private function getPage(string $url) : array
    {
        try {
            $response = $this->connection->get($url, $this->urlParams);
            return Response::toArray($response);
        } catch (\Exception $e) {
            throw new CronofyException($e->getMessage(), $e->getCode(), Response::toArray($e->getResponse()));
        }
    }
}
