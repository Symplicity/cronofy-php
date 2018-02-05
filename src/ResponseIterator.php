<?php

namespace Cronofy;

use Cronofy\Exception\CronofyException;
use Cronofy\Interfaces\ConnectionInterface;

class ResponseIterator
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
        return self;
    }

    public function each(){
        $page = $this->firstPage;

        for($i = 0; $i < count($page[$this->items_key]); $i++){
            yield $page[$this->items_key][$i];
        }

        while(isset($page["pages"]["next_page"])){
            $page = $this->getPage($page["pages"]["next_page"]);

            for($i = 0; $i < count($page[$this->items_key]); $i++){
                yield $page[$this->items_key][$i];
            }
        }
    }

    private function getPage(string $url) : array
    {
        try {
            $response = $this->connection->get($url, $this->urlParams);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            throw new CronofyException();
        }
    }
}