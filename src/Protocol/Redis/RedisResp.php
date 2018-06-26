<?php

namespace SimpleWorkerman\Protocol\Redis;

class RedisResp
{
    const RESP_STRING = "+";
    const RESP_ERROR = "-";
    const RESP_INTEGER = ":";
    const RESP_BULK_STRING = "$";
    const RESP_ARRAY = "*";

    public $buffer;
    public $response;


    public function __construct($buffer)
    {
        $this->buffer = $buffer;
    }

    public function parseResp()
    {
        if (strlen($this->buffer) <= 0) {
            return 0;
        }

        switch ($this->buffer[0]) {
        case self::RESP_STRING:
            return $this->parseRespString();
        case self::RESP_INTEGER:
            return $this->parseRespInteger();
        case self::RESP_BULK_STRING:
            return $this->parseRespBulkString();
        case self::RESP_ARRAY:
            return $this->parseRespArray();
        }
    }

    private function parseRespString()
    {
        $pos = strpos($this->buffer, "\r\n");
        if ($pos === false) {
            return 0;
        }

        $this->response = substr($this->buffer, 1, $pos-1);
        $this->buffer = substr($this->buffer, $pos + 2);
        return $pos + 2;
    }

    private function parseRespError()
    {
        $pos = strpos($this->buffer, "\r\n");
        if ($pos === false) {
            return 0;
        }

        $this->response = substr($this->buffer, 1, $pos-1);
        $this->buffer = substr($this->buffer, $pos + 2);
        return $pos + 2;
    }

    private function parseRespInteger()
    {
        $pos = strpos($this->buffer, "\r\n");
        if ($pos === false) {
            return 0;
        }

        $this->response = (int)substr($this->buffer, 1, $pos-1);
        $this->buffer = substr($this->buffer, $pos + 2);
        return $pos + 2;
    }

    private function parseRespBulkString()
    {
        $num_pos = strpos($this->buffer, "\r\n");
        if ($num_pos === false) {
            return 0;
        }

        $str_len = (int)substr($this->buffer, 1, $num_pos);
        $sub_str = substr($this->buffer, $num_pos + 2);
        if (strlen($sub_str) < $str_len + 2) {
            return 0;
        }

        $response_len = $num_pos + 2 + $str_len + 2;
        $this->response = substr($this->buffer, $num_pos+2, $str_len);
        $this->buffer = substr($this->buffer, $response_len);
        return $response_len;
    }

    private function parseRespArray()
    {
        $pos = strpos($this->buffer, "\r\n");
        if ($pos === false) {
            return 0;
        }

        $response = [];

        $arr_len = (int)substr($this->buffer, 1, $pos);
        $total = $pos + 2;
        $this->buffer = substr($this->buffer, $pos + 2);
        for ($i = 0; $i < $arr_len; $i++) {
            $ret = $this->parseResp();
            if ($ret == 0) {
                $this->response = null;
                return 0;
            }
            $response[] = $this->response;
            $total += $ret;
        }

        $this->response = $response;
        return $total;
    }
}