<?php

namespace SimpleWorkerman\Timer;

interface TimerInterface
{
    public static function add($sec, callable $cb, $persist = true);
    public static function del($timer_id);
    public static function delAll();
}