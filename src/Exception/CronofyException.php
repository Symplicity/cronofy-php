<?php

namespace Cronofy\Exception;

class CronofyException extends \Exception
{
    private $error_details;

    public function __construct($message, $code, $error_details = null)
    {
        $this->error_details = $error_details;

        parent::__construct($message, $code, null);
    }

    public function error_details()
    {
        return $this->error_details;
    }
}