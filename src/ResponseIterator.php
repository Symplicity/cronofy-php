<?php

namespace Cronofy;

class ResponseIterator
{
    private $cronofy;
    private $items_key;
    private $auth_headers;
    private $url;
    private $url_params;

    public function __construct($cronofy, $items_key, $auth_headers, $url, $url_params){
        $this->cronofy = $cronofy;
        $this->items_key = $items_key;
        $this->auth_headers = $auth_headers;
        $this->url = $url;
        $this->url_params = $url_params;
        $this->first_page = $this->get_page($url, $url_params);
    }

    public function each(){
        $page = $this->first_page;

        for($i = 0; $i < count($page[$this->items_key]); $i++){
            yield $page[$this->items_key][$i];
        }

        while(isset($page["pages"]["next_page"])){
            $page = $this->get_page($page["pages"]["next_page"]);

            for($i = 0; $i < count($page[$this->items_key]); $i++){
                yield $page[$this->items_key][$i];
            }
        }
    }

    private function get_page($url, $url_params=""){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url.$url_params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->auth_headers);
        curl_setopt($curl, CURLOPT_USERAGENT, Cronofy::USERAGENT);
        // empty string means send all supported encoding types
        curl_setopt($curl, CURLOPT_ENCODING, '');
        $result = curl_exec($curl);
        if (curl_errno($curl) > 0) {
            throw new CronofyException(curl_error($curl), 2);
        }
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $this->cronofy->handle_response($result, $status_code);
    }
}