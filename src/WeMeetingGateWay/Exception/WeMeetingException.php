<?php

namespace WeMeetingGateWay\Exception;

class WeMeetingException extends \Exception
{
    public function __toString()
    {
        return "[" . __CLASS__ . "]" . " error: " . $this->getMessage();
    }
}